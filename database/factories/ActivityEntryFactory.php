<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['practice_id' => \App\Models\Practice::factory(), 'action' => 'practice.updated', 'metadata' => []];
    }
}
