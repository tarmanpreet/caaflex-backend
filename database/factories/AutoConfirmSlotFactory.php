<?php

namespace Database\Factories;

use App\Models\AutoConfirmSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutoConfirmSlot>
 */
class AutoConfirmSlotFactory extends Factory
{
    protected $model = AutoConfirmSlot::class;

    public function definition(): array
    {
        return [
            'day_of_week' => $this->faker->numberBetween(1, 5),
            'time_from' => '09:00:00',
            'time_to' => '17:00:00',
        ];
    }
}
