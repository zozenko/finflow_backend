<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Спершу створюємо дефолтну групу, бо категорії прив'язані до неї
        $defaultGroup = $user->groups()->create([
            'name' => 'Default',
            'icon_key' => 'folder',
            'color' => '#CCCCCC'
        ]);

        $defaultCategories = [
            [
                'name' => 'Food',
                'icon_key' => 'food',
                'color' => '#FF5733',
                'group_id' => $defaultGroup->id,
                'sort_order' => 1
            ],
            [
                'name' => 'Pets',
                'icon_key' => 'pets',
                'color' => '#FFC300',
                'group_id' => $defaultGroup->id,
                'sort_order' => 2
            ],
            [
                'name' => 'Family',
                'icon_key' => 'family',
                'color' => '#DAF7A6',
                'group_id' => $defaultGroup->id,
                'sort_order' => 3
            ],
            [
                'name' => 'Salary',
                'icon_key' => 'cash',
                'color' => '#2ECC71',
                'group_id' => $defaultGroup->id,
                'sort_order' => 4
            ],
            [
                'name' => 'Investments',
                'icon_key' => 'invest',
                'color' => '#1ABC9C',
                'group_id' => $defaultGroup->id,
                'sort_order' => 5
            ],
        ];

        foreach ($defaultCategories as $category) {
            $user->categories()->create($category);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
