<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Get the first user to assign these categories
        $user = User::first();

        if (!$user) return;

        /**
         * 1. Get or Create the Default Group for this user
         */
        $defaultGroup = Group::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Default'],
            ['icon_key' => 'folder', 'color' => '#CCCCCC']
        );

        $defaultGroupId = $defaultGroup->id;

        $categories = [
            [
                'name' => 'Food',
                'icon_key' => 'food',
                'color' => '#FF5733',
                'group_id' => $defaultGroupId,
                'sort_order' => 1
            ],
            [
                'name' => 'Pets',
                'icon_key' => 'pets',
                'color' => '#FFC300',
                'group_id' => $defaultGroupId,
                'sort_order' => 2
            ],
            [
                'name' => 'Family',
                'icon_key' => 'family',
                'color' => '#DAF7A6',
                'group_id' => $defaultGroupId,
                'sort_order' => 3
            ],
            [
                'name' => 'Salary',
                'icon_key' => 'cash',
                'color' => '#2ECC71',
                'group_id' => $defaultGroupId,
                'sort_order' => 4
            ],
            [
                'name' => 'Investments',
                'icon_key' => 'invest',
                'color' => '#1ABC9C',
                'group_id' => $defaultGroupId,
                'sort_order' => 5
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'user_id'     => $user->id,
                'group_id'    => $category['group_id'],
                'name'        => $category['name'],
                'icon_key'    => $category['icon_key'],
                'color'       => $category['color'],
                'sort_order'  => $category['sort_order'],
            ]);
        }
    }
}
