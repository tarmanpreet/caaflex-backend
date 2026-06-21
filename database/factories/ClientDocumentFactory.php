<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientDocument>
 */
class ClientDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_profile_id' => ClientProfile::factory(),
            'uploaded_by' => null,
            'original_name' => fake()->word() . '.pdf',
            'disk_path' => 'client-documents/1/' . fake()->slug() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 5000000),
            'description' => fake()->optional(0.5)->sentence(),
        ];
    }
}
