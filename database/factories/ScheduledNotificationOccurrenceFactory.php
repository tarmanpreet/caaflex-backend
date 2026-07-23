<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\ScheduledNotificationOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledNotificationOccurrence>
 */
class ScheduledNotificationOccurrenceFactory extends Factory
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
            'event_key' => 'appointments.reminder',
            'subject_type' => Appointment::class,
            'subject_id' => Appointment::factory(),
            'minutes_before' => 60,
            'subject_scheduled_at' => now()->addHours(2),
            'scheduled_for' => now()->addHour(),
            'expires_at' => now()->addHours(2)->addMinutes(5),
            'status' => ScheduledNotificationOccurrence::STATUS_PENDING,
            'deduplication_key' => hash('sha256', fake()->uuid()),
        ];
    }
}
