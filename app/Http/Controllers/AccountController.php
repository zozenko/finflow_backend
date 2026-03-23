<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display a listing of the user's accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()->accounts()->get();
        return response()->json($accounts);
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'type'     => ['required', Rule::in(['cash', 'card', 'savings', 'credit'])],
            'currency' => 'required|string|size:3',
            'balance'  => 'numeric',
        ]);

        $account = $request->user()->accounts()->create($validated);

        return response()->json($account, 201);
    }

    /**
     * Display the specified account.
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($account);
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => ['sometimes', 'required', Rule::in(['cash', 'card', 'savings', 'credit'])],
            'currency' => 'required|string|size:3',
        ]);

        $account->update($validated);

        return response()->json($account);
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        // Logic: Forbid deletion if the balance is not zero
        if ((float) $account->balance !== 0.0) {
            return response()->json([
                'message' => 'Cannot delete an account with a non-zero balance. Please transfer funds first.'
            ], 400);
        }

        $account->delete();

        return response()->json(null, 204);
    }
}
