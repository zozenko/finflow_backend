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

        // 1. Create two base accounts safely
        $accounts = [
            [
                'name'     => 'Cash',
                'type'     => 'cash',
                'currency' => 'UAH',
                'balance'  => 0,
            ],
            [
                'name'     => 'Card',
                'type'     => 'card',
                'currency' => 'UAH',
                'balance'  => 0,
            ],
        ];

        foreach ($accounts as $accountData) {
            $user->accounts()->updateOrCreate(
                ['name' => $accountData['name']],
                $accountData
            );
        }

        // 2. Create the base group and store the object to get its real ID
        $groupData = [
            'name'       => 'General',
            'icon_key'   => 'Home',
            'sort_order' => 1,
            'color'      => '#10B981'
        ];

        $standardGroup = $user->groups()->updateOrCreate(
            ['name' => $groupData['name']],
            $groupData
        );

        // 3. Create base categories using the dynamic group_id
        $categories = [
            ['name' => 'Home',     'icon_key' => 'Home',           'sort_order' => 1, 'color' => '#10B981'],
            ['name' => 'Food',     'icon_key' => 'ShoppingBasket', 'sort_order' => 2, 'color' => '#F59E0B'],
            ['name' => 'Medicine', 'icon_key' => 'Pill',           'sort_order' => 3, 'color' => '#EF4444'],
            ['name' => 'Family',   'icon_key' => 'Users',          'sort_order' => 4, 'color' => '#06B6D4'],
            ['name' => 'Rest',     'icon_key' => 'Gamepad2',       'sort_order' => 5, 'color' => '#8B5CF6'],
        ];

        foreach ($categories as $categoryData) {
            $user->categories()->updateOrCreate(
                ['name' => $categoryData['name']],
                array_merge($categoryData, ['group_id' => $standardGroup->id])
            );
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
}
