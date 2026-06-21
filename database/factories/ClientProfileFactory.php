<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientProfile>
 */
class ClientProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->bothify('+39 ### ######'),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'fiscal_code' => fake()->optional(0.5)->regexify('[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]'),
            'email' => fake()->optional(0.5)->safeEmail(),
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'province' => fake()->optional()->stateAbbr(),
            'postal_code' => fake()->optional()->numerify('#####'),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the profile should be linked to a user.
     */
    public function forUser($user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? null,
        ]);
    }
}
