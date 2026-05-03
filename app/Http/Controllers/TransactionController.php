<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'transaction_date');
        $sortDir = $request->query('sort_dir', 'desc');

        $transactions = $request->user()
            ->transactions()
            ->orderBy($sortBy, $sortDir)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);

        return response()->json($transactions);
    }

    public function recent(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($transactions);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($transaction);
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'title'                  => 'required|string|max:255',
            'amount'                 => 'required|numeric|min:0',
            'type'                   => 'required|in:income,expense,transfer',
            'account_id'             => [
                'required',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'to_account_id'          => [
                'nullable',
                'required_if:type,transfer',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'category_id'            => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'planned_transaction_id' => 'nullable|exists:planned_transactions,id',
            'description'            => 'nullable|string|max:1000',
            'transaction_date'       => 'nullable|date',
            'is_favorite'            => 'nullable|boolean',
        ]);

        // Auto-assign group_id based on category
        $groupId = null;
        if (!empty($fields['category_id'])) {
            $category = Category::find($fields['category_id']);
            $groupId = $category?->group_id;
        }

        $isTransfer = $fields['type'] === 'transfer';

        $transaction = $request->user()->transactions()->create([
            'title'                  => $fields['title'],
            'amount'                 => $fields['amount'],
            'type'                   => $fields['type'],
            'account_id'             => $fields['account_id'],
            'to_account_id'          => $fields['to_account_id'] ?? null,
            'category_id'            => $isTransfer ? null : ($fields['category_id'] ?? null),
            'group_id'               => $isTransfer ? null : $groupId,
            'planned_transaction_id' => $fields['planned_transaction_id'] ?? null,
            'description'            => $fields['description'] ?? null,
            'transaction_date'       => $fields['transaction_date'] ?? now(),
            'is_favorite'            => $fields['is_favorite'] ?? false,
        ]);

        if ($transaction->type === 'transfer' && !empty($fields['to_account_id'])) {
            $request->user()->transactions()->create([
                'parent_id'        => $transaction->id,
                'title'            => $transaction->title,
                'amount'           => $transaction->amount,
                'type'             => 'transfer',
                'account_id'       => $fields['to_account_id'],
                'transaction_date' => $transaction->transaction_date,
                'description'      => $transaction->description,
                'is_favorite'      => false,
            ]);
        }

        return response()->json($transaction, 201);
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Redirect child transaction editing to its parent
        if ($transaction->parent_id) {
            $transaction = Transaction::findOrFail($transaction->parent_id);
        }

        $fields = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'amount'           => 'sometimes|required|numeric',
            'type'             => 'sometimes|required|in:income,expense,transfer',
            'account_id'       => [
                'sometimes',
                'required',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'to_account_id'    => [
                'nullable',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'category_id'      => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'description'      => 'nullable|string',
            'transaction_date' => 'sometimes|required|date',
            'is_favorite'      => 'sometimes|boolean',
        ]);

        if ($request->has('category_id')) {
            $fields['group_id'] = $fields['category_id']
                ? Category::find($fields['category_id'])?->group_id
                : null;
        }

        $wasTransfer = $transaction->type === 'transfer';
        $isTransfer  = ($fields['type'] ?? $transaction->type) === 'transfer';

        if ($isTransfer) {
            $fields['category_id'] = null;
            $fields['group_id']    = null;
        }

        $transaction->update($fields);

        if ($wasTransfer && !$isTransfer) {
            // Type changed from transfer — remove child
            $transaction->child?->delete();
        } elseif (!$wasTransfer && $isTransfer && !empty($fields['to_account_id'])) {
            // Type changed to transfer — create child
            $request->user()->transactions()->create([
                'parent_id'        => $transaction->id,
                'title'            => $transaction->title,
                'amount'           => $transaction->amount,
                'type'             => 'transfer',
                'account_id'       => $fields['to_account_id'],
                'transaction_date' => $transaction->transaction_date,
                'description'      => $transaction->description,
                'is_favorite'      => false,
            ]);
        } elseif ($wasTransfer && $isTransfer && $transaction->child) {
            // Still transfer — sync child fields
            $childData = array_filter([
                'title'            => $fields['title'] ?? null,
                'amount'           => $fields['amount'] ?? null,
                'transaction_date' => $fields['transaction_date'] ?? null,
                'description'      => $fields['description'] ?? null,
                'account_id'       => $fields['to_account_id'] ?? null,
            ], fn($v) => $v !== null);

            if (!empty($childData)) {
                $transaction->child->update($childData);
            }
        }

        return response()->json($transaction);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->update(['is_favorite' => !$transaction->is_favorite]);

        return response()->json(['is_favorite' => $transaction->is_favorite]);
    }

    private function getPeriod(Request $request): array
    {
        $period = $request->query('period');

        if ($period) {
            return match ($period) {
                'today'         => [Carbon::today(), Carbon::today()->endOfDay()],
                'current_week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
                'current_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
                'last_7_days'   => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
                'last_30_days'  => [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()],
                default         => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            };
        }

        try {
            $from = $request->query('date_from')
                ? Carbon::parse($request->query('date_from'))->startOfDay()
                : Carbon::now()->startOfMonth();

            $to = $request->query('date_to')
                ? Carbon::parse($request->query('date_to'))->endOfDay()
                : Carbon::now()->endOfMonth();
        } catch (\Exception $e) {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        }

        return [$from, $to];
    }

    public function sumByGroups(Request $request): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->getPeriod($request);

        $query = $request->user()->transactions()
            ->where('type', $request->query('type', 'expense'))
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->when($request->query('account_id'), function ($q, $id) {
                return $q->where('account_id', $id);
            });

        $stats = $query->selectRaw('group_id, SUM(amount) as total')
            ->whereNotNull('group_id')
            ->groupBy('group_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'groupId' => (int) $item->group_id,
                'amount'  => (float) round($item->total, 2),
            ]);

        return response()->json([
            'period' => [
                'from' => $dateFrom->toDateTimeString(),
                'to'   => $dateTo->toDateTimeString()
            ],
            'stats' => $stats
        ]);
    }

    public function sumByCategories(Request $request): JsonResponse
    {
        if (!$request->has('group_id')) {
            return response()->json([
                'error' => 'Group ID is required to avoid data "muddle"',
                'stats' => []
            ], 422);
        }

        [$dateFrom, $dateTo] = $this->getPeriod($request);

        $stats = $request->user()->transactions()
            ->where('group_id', $request->query('group_id'))
            ->where('type', $request->query('type', 'expense'))
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->when($request->query('account_id'), function ($q, $id) {
                return $q->where('account_id', $id);
            })
            ->selectRaw('category_id, SUM(amount) as total')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'categoryId' => (int) $item->category_id,
                'amount'     => (float) round($item->total, 2),
            ]);

        return response()->json([
            'period' => [
                'from' => $dateFrom->toDateTimeString(),
                'to' => $dateTo->toDateTimeString(),
            ],
            'stats' => $stats
        ]);
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Deleting a child redirects to parent (child is removed via cascade)
        if ($transaction->parent_id) {
            $transaction = Transaction::findOrFail($transaction->parent_id);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
