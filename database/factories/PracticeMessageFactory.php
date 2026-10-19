<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PracticeMessageFactory extends Factory
{
    public function definition(): array
    {
        return ['practice_id' => \App\Models\Practice::factory(), 'user_id' => \App\Models\User::factory(), 'body' => fake()->sentence(), 'visibility' => 'public'];
    }
}
