<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->randomElement(['MI', 'RM', 'NA', 'TO', 'FI', 'BO', 'GE', 'VE']),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'vat_number' => fake()->optional(0.5)->bothify('##########'),
            'is_active' => true,
        ];
    }
}
