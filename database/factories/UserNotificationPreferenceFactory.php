<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
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
            'section' => fake()->randomElement(['appointments', 'practices', 'deadlines']),
            'enabled' => true,
            'mail_enabled' => fake()->boolean(),
            'realtime_enabled' => true,
            'reminders_configured' => false,
        ];
    }
}
