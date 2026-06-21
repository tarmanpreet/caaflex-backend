<?php

namespace Database\Factories;

use App\Models\PracticeNote;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeNote>
 */
class PracticeNoteFactory extends Factory
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
            'body' => fake()->sentence(),
        ];
    }
}
