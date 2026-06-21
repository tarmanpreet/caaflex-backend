<?php

namespace Database\Factories;

use App\Models\PracticeStatusLog;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeStatusLog>
 */
class PracticeStatusLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'user_id' => User::factory(),
            'old_status' => fake()->optional()->randomElement(Practice::STATUSES),
            'new_status' => fake()->randomElement(Practice::STATUSES),
        ];
    }
}
