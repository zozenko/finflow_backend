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
        $defaultCategories = [
            ['name' => 'Food', 'icon' => '🍕', 'color' => '#FF5733'],
            ['name' => 'Transport', 'icon' => '🚌', 'color' => '#33B5FF'],
            ['name' => 'Salary', 'icon' => '💰', 'color' => '#2ECC71'],
            ['name' => 'Entertainment', 'icon' => '🎮', 'color' => '#9B59B6'],
            ['name' => 'Shopping', 'icon' => '🛍️', 'color' => '#F1C40F'],
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
