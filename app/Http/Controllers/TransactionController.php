<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $request->user()
            ->transactions()
            ->with(['category', 'group'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'title'            => 'required|string|max:255',
            'amount'           => 'required|numeric|min:0',
            'type'             => 'required|in:income,expense',
            'category_id'      => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'description'      => 'nullable|string|max:1000',
            'transaction_date' => 'nullable|date',
            'is_favorite'      => 'nullable|boolean',
        ]);

        $groupId = null;
        if (!empty($fields['category_id'])) {
            $category = Category::find($fields['category_id']);
            $groupId = $category?->group_id;
        }

        $transaction = $request->user()->transactions()->create([
            'title'            => $fields['title'],
            'amount'           => $fields['amount'],
            'type'             => $fields['type'],
            'category_id'      => $fields['category_id'],
            'group_id'         => $groupId,
            'description'      => $fields['description'] ?? null,
            'transaction_date' => $fields['transaction_date'] ?? now(),
            'is_favorite'      => $fields['is_favorite'] ?? false,
        ]);

        return response()->json($transaction->load(['category', 'group']), 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'amount'           => 'sometimes|required|numeric',
            'type'             => 'sometimes|required|in:income,expense',
            'category_id'      => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'description'      => 'nullable|string',
            'transaction_date' => 'sometimes|required|date',
            'is_favorite'      => 'sometimes|boolean',
        ]);

        if ($request->has('category_id')) {
            $groupId = null;
            if ($fields['category_id']) {
                $category = Category::find($fields['category_id']);
                $groupId = $category?->group_id;
            }
            $fields['group_id'] = $groupId;
        }

        $transaction->update($fields);

        return response()->json($transaction->load(['category', 'group']));
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->update([
            'is_favorite' => !$transaction->is_favorite
        ]);

        return response()->json(['is_favorite' => $transaction->is_favorite]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
