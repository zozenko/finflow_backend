<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: Розрахунок витрат для всієї ГРУПИ
     */
    public function test_group_current_spending_sums_all_category_transactions_in_current_month(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Group ' . rand(1, 99999)
        ]);
        $account = Account::factory()->create(['user_id' => $user->id]);

        // Категорії в цій групі
        $catFood = Category::factory()->create(['group_id' => $group->id, 'user_id' => $user->id]);
        $catPets = Category::factory()->create(['group_id' => $group->id, 'user_id' => $user->id]);

        // 1. ВИТРАТИ цього місяця (500 + 300 = 800)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'group_id' => $group->id, // Передаємо явно, бо воно є в міграції
            'category_id' => $catFood->id,
            'amount' => 500,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'group_id' => $group->id,
            'category_id' => $catPets->id,
            'amount' => 300,
            'type' => 'expense',
            'transaction_date' => now()->startOfMonth()->addDays(2)->format('Y-m-d'),
        ]);

        // 2. ІГНОРУЄМО: Минулий місяць
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'group_id' => $group->id,
            'category_id' => $catFood->id,
            'amount' => 1000,
            'type' => 'expense',
            'transaction_date' => now()->subMonth()->format('Y-m-d'),
        ]);

        // 3. ІГНОРУЄМО: Дохід (не є витратою бюджету)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'group_id' => $group->id,
            'category_id' => $catFood->id,
            'amount' => 2000,
            'type' => 'income',
        ]);

        // Перевірка результату для групи
        $this->assertEquals(800.00, $group->fresh()->current_spending);
    }

    /**
     * Тест: Розрахунок витрат для конкретної КАТЕГОРІЇ
     */
    public function test_category_current_spending_calculation(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Group ' . rand(1, 99999)
        ]);
        $category = Category::factory()->create(['group_id' => $group->id, 'user_id' => $user->id]);
        $account = Account::factory()->create(['user_id' => $user->id]);

        // Витрата цієї категорії (450.50)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'group_id' => $group->id,
            'amount' => 450.50,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        // Витрата ІНШОЇ категорії (не має впливати)
        $otherCategory = Category::factory()->create(['group_id' => $group->id, 'user_id' => $user->id]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $otherCategory->id,
            'group_id' => $group->id,
            'amount' => 1000,
            'type' => 'expense',
        ]);

        $this->assertEquals(450.50, $category->fresh()->current_spending);
    }
}
