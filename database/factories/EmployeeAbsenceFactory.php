<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeAbsenceFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'type' => 'ferie', 'starts_at' => now()->addDay()->startOfDay(), 'ends_at' => now()->addDays(2)->startOfDay(), 'all_day' => true, 'status' => 'pending'];
    }
}
