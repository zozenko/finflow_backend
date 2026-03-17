<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Group;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TransactionTest extends TestCase
{
    use RefreshDatabase; // Automatically resets the database after each test

    #[Test]
    public function a_user_can_get_their_transactions()
    {
        // 1. Create a user instance
        $user = User::factory()->create();

        // 2. Create a group and a category (mimicking seeder logic)
        $group = Group::create([
            'user_id' => $user->id,
            'name'    => 'Test Group',
            'icon_key' => 'home'
        ]);

        $category = Category::create([
            'user_id'  => $user->id,
            'group_id' => $group->id,
            'name'     => 'Food',
            'icon_key' => 'food',
            'color'    => '#FF5733'
        ]);

        // 3. Create a transaction record
        Transaction::create([
            'user_id'          => $user->id,
            'category_id'      => $category->id,
            'group_id'         => $group->id,
            'title'            => 'Test Dinner',
            'amount'           => 100,
            'type'             => 'expense',
            'transaction_date' => now()
        ]);

        // 4. Perform a GET request as the authenticated user
        $response = $this->actingAs($user)->getJson('/api/transactions');

        // 5. Verify the response status and data count
        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    #[Test]
    public function a_user_can_toggle_transaction_favorite_status()
    {
        $user = User::factory()->create();
        $group = Group::create(['user_id' => $user->id, 'name' => 'Work', 'icon_key' => 'briefcase']);
        $category = Category::create(['user_id' => $user->id, 'group_id' => $group->id, 'name' => 'Salary', 'icon_key' => 'cash', 'color' => '#00FF00']);

        $transaction = $user->transactions()->create([
            'category_id' => $category->id,
            'group_id' => $group->id,
            'title' => 'Monthly Salary',
            'amount' => 5000,
            'type' => 'income',
            'transaction_date' => now(),
            'is_favorite' => false
        ]);

        $response = $this->actingAs($user)->patchJson("/api/transactions/{$transaction->id}/toggle-favorite");

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'is_favorite' => true
        ]);
    }

    #[Test]
    public function a_user_can_create_a_transaction()
    {
        $user = User::factory()->create();
        $group = Group::create(['user_id' => $user->id, 'name' => 'General', 'icon_key' => 'home']);
        $category = Category::create(['user_id' => $user->id, 'group_id' => $group->id, 'name' => 'Food', 'icon_key' => 'food', 'color' => '#FF5733']);

        $payload = [
            'category_id' => $category->id,
            'title' => 'Lunch',
            'amount' => 15.50,
            'type' => 'expense',
            'transaction_date' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->postJson('/api/transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', ['title' => 'Lunch']);
    }
}
