<?php

namespace Database\Factories;

use App\Models\PracticeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeType>
 */
class PracticeTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['730', 'ISEE', 'IMU_TASI', 'RED_INPS', 'SUCCESSIONE', 'BONUS_AGEVOLAZIONI', 'ALTRO', 'CU', 'IVA', 'PENSIONE']),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
        ];
    }
}
