<?php

namespace App\Http\Controllers;

use App\Models\PlannedTransaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PlannedTransactionController extends Controller
{
    /**
     * Display a listing of planned transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $planned = $request->user()
            ->plannedTransactions()
            ->orderBy('next_payment_date', 'asc')
            ->get();

        return response()->json($planned);
    }

    /**
     * Store a new planned transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'title'             => 'required|string|max:255',
            'amount'            => 'required|numeric|min:0',
            'type'              => 'required|in:income,expense,transfer',
            'frequency'         => 'required|in:daily,weekly,monthly,yearly',
            'next_payment_date' => 'required|date|after_or_equal:today',
            'account_id'        => [
                'required',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'to_account_id'     => [
                'nullable',
                'required_if:type,transfer',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'category_id'       => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'auto_execute'      => 'boolean',
            'is_active'         => 'boolean',
        ]);

        // Auto-assign group_id based on category
        $groupId = null;
        if (!empty($fields['category_id'])) {
            $category = Category::find($fields['category_id']);
            $groupId = $category?->group_id;
        }

        $planned = $request->user()->plannedTransactions()->create(array_merge($fields, [
            'group_id' => $groupId
        ]));

        return response()->json($planned->load(['category', 'group', 'account', 'toAccount']), 201);
    }

    /**
     * Update the planned transaction.
     */
    public function update(Request $request, PlannedTransaction $plannedTransaction): JsonResponse
    {
        if ($plannedTransaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'title'             => 'sometimes|required|string|max:255',
            'amount'            => 'sometimes|required|numeric|min:0',
            'type'              => 'sometimes|required|in:income,expense,transfer',
            'frequency'         => 'sometimes|required|in:daily,weekly,monthly,yearly',
            'next_payment_date' => 'sometimes|required|date',
            'account_id'        => [
                'sometimes',
                'required',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'category_id'       => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
            'auto_execute'      => 'boolean',
            'is_active'         => 'boolean',
        ]);

        if ($request->has('category_id')) {
            $fields['group_id'] = $fields['category_id']
                ? Category::find($fields['category_id'])?->group_id
                : null;
        }

        $plannedTransaction->update($fields);

        return response()->json($plannedTransaction->load(['category', 'group', 'account', 'toAccount']));
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Request $request, PlannedTransaction $plannedTransaction): JsonResponse
    {
        if ($plannedTransaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plannedTransaction->update(['is_active' => !$plannedTransaction->is_active]);

        return response()->json(['is_active' => $plannedTransaction->is_active]);
    }

    /**
     * Remove the planned transaction.
     */
    public function destroy(Request $request, PlannedTransaction $plannedTransaction): JsonResponse
    {
        if ($plannedTransaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plannedTransaction->delete();

        return response()->json(['message' => 'Planned transaction deleted successfully']);
    }
}
