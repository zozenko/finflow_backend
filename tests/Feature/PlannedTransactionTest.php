<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Group;
use App\Models\PlannedTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlannedTransactionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an auto-execute planned transaction creates a real transaction
     * and shifts the next payment date correctly.
     */
    public function test_auto_execute_planned_transaction_creates_transaction_and_updates_date(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 2000.00
        ]);

        $group = Group::factory()->create(['user_id' => $user->id, 'name' => 'Monthly Bills']);
        $category = Category::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        // 1. Create a planned transaction due today
        $today = now()->format('Y-m-d');
        $planned = PlannedTransaction::factory()->create([
            'user_id'           => $user->id,
            'account_id'        => $account->id,
            'category_id'       => $category->id,
            'group_id'          => $group->id,
            'amount'            => 150.00,
            'type'              => 'expense',
            'frequency'         => 'monthly',
            'next_payment_date' => $today,
            'is_active'         => true,
            'auto_execute'      => true,
        ]);

        // 2. Run the console command
        $this->artisan('planned-transactions:process');

        // 3. Assertions
        // Verify real transaction creation
        $this->assertDatabaseHas('transactions', [
            'planned_transaction_id' => $planned->id,
            'amount'                 => 150.00,
            'user_id'                => $user->id
        ]);

        // Verify balance update
        $this->assertEquals(1850.00, $account->fresh()->balance);

        // Verify date shift to the next month
        $expectedNextDate = now()->addMonth()->format('Y-m-d');
        $this->assertEquals(
            $expectedNextDate,
            $planned->fresh()->next_payment_date->format('Y-m-d')
        );

        // Verify the plan remains active
        $this->assertTrue((bool) $planned->fresh()->is_active);
    }
}
