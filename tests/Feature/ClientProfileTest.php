<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_profile_with_required_fields_only(): void
    {
        $profile = ClientProfile::factory()->create([
            'user_id' => null,
            'address' => null,
            'city' => null,
            'province' => null,
            'postal_code' => null,
            'fiscal_code' => null,
            'email' => null,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('client_profiles', [
            'id' => $profile->id,
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'phone' => $profile->phone,
            'user_id' => null,
        ]);
    }

    public function test_user_has_one_client_profile_relationship(): void
    {
        $user = User::factory()->create();
        $profile = ClientProfile::factory()->forUser($user)->create();

        $this->assertDatabaseHas('client_profiles', [
            'id' => $profile->id,
            'user_id' => $user->id,
        ]);

        // Test relationship works
        $this->assertTrue($user->clientProfile()->exists());
        $this->assertEquals($profile->id, $user->clientProfile->id);
    }

    public function test_client_profile_can_exist_without_user(): void
    {
        $profile = ClientProfile::factory()->create([
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('client_profiles', [
            'id' => $profile->id,
            'user_id' => null,
        ]);

        // Verify user_id is nullable
        $this->assertNull($profile->user_id);
    }
}
