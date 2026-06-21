<?php

namespace Database\Factories;

use App\Models\PracticeDocument;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeDocument>
 */
class PracticeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => fake()->word() . '.pdf',
            'disk_path' => 'practice-documents/1/' . fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 5000000),
        ];
    }
}
