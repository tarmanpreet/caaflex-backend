<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDelegationFactory extends Factory
{
    public function definition(): array
    {
        return ['client_profile_id' => \App\Models\ClientProfile::factory(), 'delegate_user_id' => \App\Models\User::factory(), 'granted_by' => \App\Models\User::factory(), 'scopes' => ['view'], 'expires_at' => now()->addYear()];
    }
}
