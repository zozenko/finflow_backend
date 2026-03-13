<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Get all transactions for the authenticated user
     */
    public function index(Request $request)
    {
        // Retrieve transactions that belong only to the current user
        // We also load the 'category' relationship to show the category name on the frontend
        $transactions = $request->user()
            ->transactions()
            ->with('category') // Eager loading the category data
            ->orderBy('transaction_date', 'desc') // Show latest transactions first
            ->get();

        return response()->json($transactions);
    }

    /**
     * Store a new transaction linked to the user and category
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'amount'           => 'required|numeric',
            'type'             => 'required|in:income,expense',
            'category_id'      => 'required|exists:categories,id',
            'description'      => 'nullable|string',
            'transaction_date' => 'nullable|date'
        ]);

        $transaction = $request->user()->transactions()->create([
            'amount'           => $fields['amount'],
            'type'             => $fields['type'],
            'category_id'      => $fields['category_id'],
            'description'      => $fields['description'],
            'transaction_date' => $fields['transaction_date'] ?? now(),
        ]);

        $transaction->load('category');
        return response()->json($transaction, 201);
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
