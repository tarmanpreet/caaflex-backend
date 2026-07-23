<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use App\Models\Practice;
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
            'tracking_code' => fn (): string => Practice::uniqueTrackingCode(),
            'client_profile_id' => ClientProfile::factory(),
            'type' => fake()->randomElement(Practice::TYPES),
            'status' => fake()->randomElement(Practice::STATUSES),
            'reference_year' => fake()->numberBetween(2022, 2026),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
