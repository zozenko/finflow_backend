<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test; // Додано для підтримки атрибутів

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_create_and_list_categories()
    {
        $user = User::factory()->create();
        // Створюємо групу, щоб уникнути проблем із зовнішніми ключами
        $group = Group::create([
            'user_id' => $user->id,
            'name' => 'Home',
            'icon_key' => 'home'
        ]);

        // 1. Тест створення категорії
        $response = $this->actingAs($user)->postJson('/api/categories', [
            'group_id' => $group->id,
            'name' => 'Rent',
            'icon_key' => 'key',
            'color' => '#FF0000'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['name' => 'Rent', 'icon_key' => 'key']);

        // 2. Тест отримання списку категорій
        $response = $this->actingAs($user)->getJson('/api/categories');

        $response->assertStatus(200);

        // Використовуємо assertJsonFragment, якщо сідер додає свої категорії, 
        // або залишаємо assertJsonCount(1), якщо впевнені, що база чиста.
        $response->assertJsonFragment(['name' => 'Rent']);
    }

    #[Test]
    public function a_user_cannot_access_another_users_category()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $groupA = Group::create(['user_id' => $userA->id, 'name' => 'User A Group', 'icon_key' => 'lock']);

        $category = Category::create([
            'user_id' => $userA->id,
            'group_id' => $groupA->id,
            'name' => 'Secret',
            'icon_key' => 'shield',
            'color' => '#000000'
        ]);

        // Спроба отримати доступ до категорії користувача A від імені користувача B
        $response = $this->actingAs($userB)->getJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }
}
