<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeAuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcasting_authorization_requires_a_bearer_authenticated_user(): void
    {
        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-App.Models.User.1',
        ])->assertUnauthorized();
    }

    public function test_bearer_authenticated_user_can_reach_broadcasting_authorization(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$user->id}",
            ])
            ->assertOk();
    }
}
