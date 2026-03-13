<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Get the first user to assign these categories
        $user = User::first();

        if (!$user) return;

        $categories = [
            ['name' => 'Їжа', 'icon' => '🛒', 'color' => '#FF5733'],
            ['name' => 'Транспорт', 'icon' => '🚌', 'color' => '#33B5FF'],
            ['name' => 'Зарплата', 'icon' => '💰', 'color' => '#2ECC71'],
            ['name' => 'Розваги', 'icon' => '🎬', 'color' => '#9B59B6'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'user_id' => $user->id,
            ]);
        }
    }
}
