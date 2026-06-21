<?php

namespace Database\Factories;

use App\Models\Procedure;
use App\Models\PracticeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Procedure>
 */
class ProcedureFactory extends Factory
{
    protected $model = Procedure::class;

    public function definition(): array
    {
        return [
            'procedure_type_id' => PracticeType::factory(),
            'name' => $this->faker->words(3, true),
            'default_notes' => null,
        ];
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_notes' => $this->faker->sentence(10),
        ]);
    }
}
