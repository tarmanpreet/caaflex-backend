<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\ClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Practice>
 */
class PracticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_profile_id' => ClientProfile::factory(),
            'type' => fake()->randomElement(Practice::TYPES),
            'status' => fake()->randomElement(Practice::STATUSES),
            'reference_year' => fake()->numberBetween(2022, 2026),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
