<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\PracticeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_profile_id' => ClientProfile::factory(),
            'assigned_user_id' => null,
            'practice_type_id' => PracticeType::factory(),
            'practice_id' => null,
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => 60,
            'status' => $this->faker->randomElement(Appointment::STATUSES),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
