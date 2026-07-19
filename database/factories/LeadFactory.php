<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'source' => fake()->randomElement(['web', 'referral', 'cold_call', 'event', 'other']),
            'status' => 'new',
            'expected_value' => fake()->randomFloat(2, 500, 25000),
            'assigned_to' => User::factory(), // Creates a rep by default
        ];
    }
}
