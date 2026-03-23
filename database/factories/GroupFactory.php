<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => $this->faker->randomElement(['Home', 'Food', 'Car', 'Work', 'Health']),
            'icon_key'   => $this->faker->randomElement(['home', 'utensils', 'car', 'briefcase', 'heart']),
            'color'      => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'user_id'    => User::factory(),
        ];
    }
}
