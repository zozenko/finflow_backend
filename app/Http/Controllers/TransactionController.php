<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->with(['category', 'group', 'account', 'toAccount'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json($transactions);
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

        $transaction = $request->user()->transactions()->create([
            'title'                  => $fields['title'],
            'amount'                 => $fields['amount'],
            'type'                   => $fields['type'],
            'account_id'             => $fields['account_id'],
            'to_account_id'          => $fields['to_account_id'] ?? null,
            'category_id'            => $fields['category_id'] ?? null,
            'group_id'               => $groupId,
            'planned_transaction_id' => $fields['planned_transaction_id'] ?? null,
            'description'            => $fields['description'] ?? null,
            'transaction_date'       => $fields['transaction_date'] ?? now(),
            'is_favorite'            => $fields['is_favorite'] ?? false,
        ]);

        return response()->json($transaction->load(['category', 'group', 'account', 'toAccount']), 201);
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        $transaction->update($fields);

        return response()->json($transaction->load(['category', 'group', 'account', 'toAccount']));
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

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
