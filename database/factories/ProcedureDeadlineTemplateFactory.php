<?php

namespace Database\Factories;

use App\Models\Procedure;
use App\Models\ProcedureDeadlineTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureDeadlineTemplate>
 */
class ProcedureDeadlineTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procedure_id' => Procedure::factory(),
            'title' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'offset_days' => fake()->numberBetween(0, 30),
            'offset_hours' => fake()->numberBetween(0, 23),
            'priority' => fake()->numberBetween(1, 4),
            'position' => 0,
        ];
    }
}
