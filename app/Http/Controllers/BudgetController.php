<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Display a listing of the user's budgets.
     */
    public function index(Request $request): JsonResponse
    {
        $budgets = $request->user()
            ->budgets()
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($budgets);
    }

    /**
     * Store a newly created budget in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'period'      => 'nullable|string|in:monthly,weekly,yearly',
            'start_date'  => 'required|date',
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'group_id'    => [
                'nullable',
                Rule::exists('groups', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
        ]);

        // Logic: A budget must belong to either a category OR a group (not both and not none)
        $hasCategory = !empty($fields['category_id']);
        $hasGroup = !empty($fields['group_id']);

        if (($hasCategory && $hasGroup) || (!$hasCategory && !$hasGroup)) {
            return response()->json([
                'message' => 'Please provide either a category_id or a group_id, but not both.'
            ], 422);
        }

        $budget = $request->user()->budgets()->create($fields);

        return response()->json($budget->load(['category', 'group']), 201);
    }

    /**
     * Display the specified budget.
     */
    public function show(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($budget->load(['category', 'group']));
    }

    /**
     * Update the specified budget in storage.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $fields = $request->validate([
            'amount'     => 'sometimes|required|numeric|min:0.01',
            'period'     => 'sometimes|required|string|in:monthly,weekly,yearly',
            'start_date' => 'sometimes|required|date',
        ]);

        $budget->update($fields);

        return response()->json($budget);
    }

    /**
     * Remove the specified budget from storage.
     */
    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully.']);
    }
}
