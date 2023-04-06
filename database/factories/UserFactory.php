<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'name' => fake()->firstName() . ' ' . fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'user_handle' => fake()->word(),
            'is_admin' => TRUE, #todo this shouldn't be just TRUE. maybe a dedicated admin factory?
        ];
    }
}
