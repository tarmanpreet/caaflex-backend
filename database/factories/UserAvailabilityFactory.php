<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAvailability>
 */
class UserAvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'day_of_week' => $this->faker->numberBetween(1, 5),
            'time_from' => '09:00',
            'time_to' => '17:00',
        ];
    }
}
