<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) return;

        Group::create([
            'user_id'    => $user->id,
            'name'       => 'Default',
            'icon_key'   => 'home',
            'sort_order' => 0,
        ]);
    }
}
