<?php

namespace Database\Factories;

use App\Models\DeadlineReminder;
use App\Models\PracticeDeadline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeadlineReminder>
 */
class DeadlineReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            DeadlineReminder::TYPE_EMAIL,
            DeadlineReminder::TYPE_IN_APP,
        ];

        $minutesOptions = [
            DeadlineReminder::MINUTES_DAY,
            DeadlineReminder::MINUTES_HOUR,
            120,
            30,
        ];

        return [
            'deadline_id' => PracticeDeadline::factory(),
            'type' => fake()->randomElement($types),
            'minutes_before' => fake()->randomElement($minutesOptions),
            'sent' => fake()->boolean(30),
            'sent_at' => null,
        ];
    }

    /**
     * Indicate that the reminder is an email type.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeadlineReminder::TYPE_EMAIL,
        ]);
    }

    /**
     * Indicate that the reminder is an in-app type.
     */
    public function inApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeadlineReminder::TYPE_IN_APP,
        ]);
    }

    /**
     * Indicate that the reminder has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent' => true,
            'sent_at' => now(),
        ]);
    }
}
