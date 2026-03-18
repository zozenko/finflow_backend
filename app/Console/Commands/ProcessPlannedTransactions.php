<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PlannedTransaction;
use App\Models\Transaction;

class ProcessPlannedTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'planned-transactions:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all pending planned transactions for today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Використовуй карбон без форматування у рядок для надійності
        $today = now()->startOfDay();

        $plannedTransactions = PlannedTransaction::whereDate('next_payment_date', '<=', $today)
            ->where('is_active', true)
            ->where('auto_execute', true)
            ->get();

        // Додай цей рядок, щоб ми бачили в консолі, чи знайшли щось
        if ($plannedTransactions->isEmpty()) {
            $this->warn("No transactions found for date: " . $today->toDateString());
        }

        foreach ($plannedTransactions as $planned) {
            DB::transaction(function () use ($planned) {
                // 1. Create the real transaction
                Transaction::create([
                    'user_id'                => $planned->user_id,
                    'account_id'             => $planned->account_id,
                    'to_account_id'          => $planned->to_account_id,
                    'group_id'               => $planned->group_id,
                    'category_id'            => $planned->category_id,
                    'planned_transaction_id' => $planned->id,
                    'title'                  => $planned->title,
                    'amount'                 => $planned->amount,
                    'type'                   => $planned->type,
                    'transaction_date'       => now(),
                ]);

                // 2. Calculate the next payment date based on frequency
                $nextDate = match ($planned->frequency) {
                    'daily'   => now()->addDay(),
                    'weekly'  => now()->addWeek(),
                    'monthly' => now()->addMonth(),
                    'yearly'  => now()->addYear(),
                    default   => null,
                };

                // 3. Update the planned transaction for the next occurrence
                if ($nextDate) {
                    $planned->update([
                        'next_payment_date' => $nextDate->format('Y-m-d')
                    ]);
                } else {
                    // If no frequency, deactivate the plan
                    $planned->update(['is_active' => false]);
                }
            });
        }

        $this->info("Successfully processed {$plannedTransactions->count()} tasks.");
    }
}
