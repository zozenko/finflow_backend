<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'     => $this->faker->randomElement(['Monobank', 'Cash', 'Savings', 'Privat24']),
            'type'     => $this->faker->randomElement(['card', 'cash', 'savings']),
            'currency' => 'UAH',
            'balance'  => $this->faker->randomFloat(2, 0, 10000),
            'user_id'  => User::factory(),
        ];
    }
}
