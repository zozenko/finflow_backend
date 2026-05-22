<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBalanceTest extends TestCase
{
    use RefreshDatabase; // This resets the database before each test

    public function test_transaction_creation_updates_account_balance(): void
    {
        $this->withoutExceptionHandling();

        // 1. Create a user
        $user = User::factory()->create();

        // 2. Retrieve the auto-created 'Card' account or create it if missing
        $cardAccount = $user->accounts()->where('name', 'Card')->first()
            ?? \App\Models\Account::factory()->create(['user_id' => $user->id, 'name' => 'Card', 'balance' => 0]);

        // Гарантовано отримуємо або створюємо групу 'Food' для цього юзера
        $foodGroup = $user->groups()->where('name', 'Food')->first()
            ?? \App\Models\Group::factory()->create(['user_id' => $user->id, 'name' => 'Food']);

        // 3. Create a category linked to our guaranteed group
        $category = Category::factory()->create([
            'user_id'  => $user->id,
            'group_id' => $foodGroup->id,
            'name'     => 'Groceries'
        ]);

        // 4. Act: Post a new transaction through the API
        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'title'            => 'Supermarket Trip',
            'amount'           => 750.50,
            'type'             => 'expense',
            'account_id'       => $cardAccount->id,
            'category_id'      => $category->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        // 5. Assert: Check response status and balance logic
        $response->assertStatus(201);

        // Assert balance: 0 - 750.50 = -750.50
        $this->assertEquals(-750.50, $cardAccount->fresh()->balance);

        // Check if the transaction exists in the database
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount'  => 750.50,
            'type'    => 'expense'
        ]);
    }

    /**
     * Test that deleting a transaction restores the account balance.
     */
    public function test_transaction_deletion_restores_account_balance(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00,
        ]);

        // Створюємо групу з гарантовано унікальною назвою для цього юзера
        $group = \App\Models\Group::factory()->create([
            'user_id' => $user->id,
            'name' => 'Unique Group ' . bin2hex(random_bytes(5)),
        ]);

        $category = \App\Models\Category::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        // 1. Створюємо витрату на 400 грн, передаючи створені id
        $transaction = \App\Models\Transaction::factory()->create([
            'user_id'     => $user->id,
            'account_id'  => $account->id,
            'group_id'    => $group->id,    // Передаємо явно
            'category_id' => $category->id, // Передаємо явно
            'amount'      => 400.00,
            'type'        => 'expense',
        ]);

        // Перевіряємо, що баланс став 600
        $this->assertEquals(600.00, $account->fresh()->balance);

        // 2. Видаляємо транзакцію через API
        $response = $this->actingAs($user)
            ->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);

        // 3. Перевіряємо, що баланс ПОВЕРНУВСЯ до 1000
        $this->assertEquals(1000.00, $account->fresh()->balance);

        // Перевіряємо відсутність у базі
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_transfer_transaction_updates_both_accounts_balances(): void
    {
        $user = \App\Models\User::factory()->create();

        // Створюємо два рахунки
        $sourceAccount = \App\Models\Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
        $targetAccount = \App\Models\Account::factory()->create(['user_id' => $user->id, 'balance' => 200]);

        // Створюємо унікальну групу та категорію заздалегідь
        $group = \App\Models\Group::factory()->create([
            'user_id' => $user->id,
            'name' => 'Transfer Group ' . uniqid()
        ]);

        $category = \App\Models\Category::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id
        ]);

        // 1. Створюємо переказ на 300 грн
        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'title'         => 'Transfer to Savings',
            'amount'        => 300.00,
            'type'          => 'transfer',
            'account_id'    => $sourceAccount->id,
            'to_account_id' => $targetAccount->id,
            'category_id'   => $category->id,
            'group_id'      => $group->id, // Важливо передати, бо воно є в міграції
        ]);

        $response->assertStatus(201);

        // 2. Перевіряємо баланси
        // З першого зняло: 1000 - 300 = 700
        $this->assertEquals(700.00, $sourceAccount->fresh()->balance);
        // На другий прийшло: 200 + 300 = 500
        $this->assertEquals(500.00, $targetAccount->fresh()->balance);
    }
}
