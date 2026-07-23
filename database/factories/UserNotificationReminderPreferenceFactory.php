<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationReminderPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotificationReminderPreference>
 */
class UserNotificationReminderPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'section' => fake()->randomElement(['appointments', 'deadlines']),
            'minutes_before' => fake()->randomElement([10080, 1440, 60, 0]),
        ];
    }
}
