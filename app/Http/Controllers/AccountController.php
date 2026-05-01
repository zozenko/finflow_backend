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
        $accounts = $request->user()->accounts()->orderBy('name', 'asc')->get();
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

        $accountCount = $request->user()->accounts()->count();
        if ($accountCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last account.'
            ], 422);
        }

        $fields = $request->validate([
            'reassign_to_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
        ]);

        if (!empty($fields['reassign_to_account_id'])) {
            $target = Account::find($fields['reassign_to_account_id']);
            $account->transactions()->update(['account_id' => $target->id]);
            $target->increment('balance', $account->balance);
        }

        $account->delete();

        return response()->json(null, 204);
    }
}
