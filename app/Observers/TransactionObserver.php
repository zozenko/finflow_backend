<?php

namespace App\Observers;

use App\Models\Transaction;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->increment('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $account->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'transfer' && $transaction->to_account_id) {
            // Money leaves the source account
            $account->decrement('balance', $transaction->amount);
            // Money enters the destination account
            $transaction->toAccount->increment('balance', $transaction->amount);
        }
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Check if financial data has changed to avoid unnecessary queries 
        // (e.g., when only 'is_favorite' or 'description' is updated)
        if (!$transaction->wasChanged(['amount', 'type', 'account_id', 'to_account_id'])) {
            return;
        }

        // 1. REVERT OLD TRANSACTION IMPACT
        // Get data as it was before the update
        $oldAmount = $transaction->getOriginal('amount');
        $oldType   = $transaction->getOriginal('type');
        $oldAccountId = $transaction->getOriginal('account_id');
        $oldToAccountId = $transaction->getOriginal('to_account_id');

        $oldAccount = \App\Models\Account::find($oldAccountId);

        if ($oldType === 'income') {
            $oldAccount->decrement('balance', $oldAmount);
        } elseif ($oldType === 'expense') {
            $oldAccount->increment('balance', $oldAmount);
        } elseif ($oldType === 'transfer' && $oldToAccountId) {
            $oldAccount->increment('balance', $oldAmount);
            \App\Models\Account::find($oldToAccountId)?->decrement('balance', $oldAmount);
        }

        // 2. APPLY NEW TRANSACTION IMPACT
        // Use the 'created' logic to apply new values
        $this->created($transaction);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $account->increment('balance', $transaction->amount);
        } elseif ($transaction->type === 'transfer' && $transaction->to_account_id) {
            // Revert transfer: return money to source, take from destination
            $account->increment('balance', $transaction->amount);
            $transaction->toAccount->decrement('balance', $transaction->amount);
        }
    }
}
