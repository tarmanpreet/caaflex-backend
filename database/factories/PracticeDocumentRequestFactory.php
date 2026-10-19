<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PracticeDocumentRequestFactory extends Factory
{
    public function definition(): array
    {
        return ['practice_id' => \App\Models\Practice::factory(), 'title' => 'Documento di identità', 'status' => 'requested'];
    }
}
