<?php

namespace Database\Factories;

use App\Models\PracticeDeadline;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeDeadline>
 */
class PracticeDeadlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            PracticeDeadline::STATUS_PENDING,
            PracticeDeadline::STATUS_IN_PROGRESS,
            PracticeDeadline::STATUS_COMPLETED,
            PracticeDeadline::STATUS_CANCELLED,
        ];

        return [
            'practice_id' => Practice::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->paragraph(),
            'deadline_at' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement($statuses),
            'priority' => fake()->randomElement([
                PracticeDeadline::PRIORITY_URGENT,
                PracticeDeadline::PRIORITY_HIGH,
                PracticeDeadline::PRIORITY_MEDIUM,
                PracticeDeadline::PRIORITY_LOW,
            ]),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the deadline is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the deadline is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => PracticeDeadline::PRIORITY_URGENT,
        ]);
    }
}
