<?php

namespace Database\Factories;

use App\Models\PlannedTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannedTransaction>
 */
class PlannedTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'           => \App\Models\User::factory(),
            'account_id'        => \App\Models\Account::factory(),
            'category_id'       => \App\Models\Category::factory(),
            'group_id'          => \App\Models\Group::factory(),
            'title'             => $this->faker->sentence(3),
            'amount'            => $this->faker->randomFloat(2, 10, 500),
            'type'              => 'expense',
            'frequency'         => 'monthly',
            'next_payment_date' => now()->format('Y-m-d'),
            'is_active'         => true,
            'auto_execute'      => true,
        ];
    }
}
