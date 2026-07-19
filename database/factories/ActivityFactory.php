<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['call', 'email', 'meeting', 'note']),
            'body' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
