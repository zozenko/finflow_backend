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

        // 2. Create base groups safely
        $groups = [
            ['name' => 'Home',     'icon_key' => 'home',   'sort_order' => 1],
            ['name' => 'Food',     'icon_key' => 'food',   'sort_order' => 2],
            ['name' => 'Medicine', 'icon_key' => 'pills',  'sort_order' => 3],
            ['name' => 'Family',   'icon_key' => 'family', 'sort_order' => 4],
            ['name' => 'Rest',     'icon_key' => 'games',  'sort_order' => 5],
        ];

        foreach ($groups as $groupData) {
            $user->groups()->updateOrCreate(
                ['name' => $groupData['name']],
                $groupData
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
