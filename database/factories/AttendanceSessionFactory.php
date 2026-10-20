<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceSessionFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'started_at' => now()->subHours(8), 'ended_at' => now()];
    }
}
