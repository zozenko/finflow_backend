<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'            => $this->faker->sentence(2), // Напр. "Buying bread"
            'amount'           => $this->faker->randomFloat(2, 10, 2000), // Від 10 до 2000 грн
            'type'             => $this->faker->randomElement(['expense', 'income', 'transfer']),
            'transaction_date' => now(),
            'user_id'          => User::factory(),
            'account_id'       => Account::factory(),
            'category_id'      => Category::factory(),
            'description'      => $this->faker->text(50),
        ];
    }
}
