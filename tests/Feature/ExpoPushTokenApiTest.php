<?php

namespace Tests\Feature;

use App\Models\ExpoPushToken;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpoPushTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_an_encrypted_expo_push_token(): void
    {
        $user = User::factory()->create();
        $token = 'ExpoPushToken[device_token_123]';

        $response = $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', $this->payload($token));

        $response->assertCreated()
            ->assertJsonPath('message', 'Token push registrato.')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.platform', 'ios')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.token_hash');

        $storedToken = ExpoPushToken::query()->firstOrFail();
        $this->assertSame($token, $storedToken->token);
        $this->assertNotSame($token, DB::table('expo_push_tokens')->value('token'));
        $this->assertSame(hash('sha256', $token), DB::table('expo_push_tokens')->value('token_hash'));
    }

    public function test_registration_is_idempotent_and_refreshes_device_metadata(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload('ExponentPushToken[idempotent_token]');

        $firstResponse = $this->mobileSession($user)->postJson('/api/v1/push-tokens', $payload);
        $tokenId = $firstResponse->json('data.id');

        $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', [
                ...$payload,
                'device_name' => 'iPhone aggiornato',
                'app_version' => '2.0.0',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $tokenId)
            ->assertJsonPath('data.device_name', 'iPhone aggiornato')
            ->assertJsonPath('data.app_version', '2.0.0');

        $this->assertSame(1, ExpoPushToken::query()->count());
    }

    public function test_new_token_for_the_same_device_replaces_the_stale_token(): void
    {
        $user = User::factory()->create();

        $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[old_token]'))
            ->assertCreated();

        $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[new_token]'))
            ->assertOk();

        $this->assertSame(1, ExpoPushToken::query()->count());
        $this->assertSame('ExpoPushToken[new_token]', ExpoPushToken::query()->firstOrFail()->token);
    }

    public function test_registration_is_rejected_when_all_realtime_preferences_are_disabled(): void
    {
        $user = User::factory()->create();

        foreach (array_keys(config('notifications.sections')) as $section) {
            UserNotificationPreference::query()->create([
                'user_id' => $user->id,
                'section' => $section,
                'enabled' => true,
                'mail_enabled' => true,
                'realtime_enabled' => false,
            ]);
        }

        $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[disabled_token]'))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Le notifiche in tempo reale sono disabilitate nelle preferenze.');

        $this->assertSame(0, ExpoPushToken::query()->count());
    }

    public function test_registration_validates_expo_token_and_device_metadata(): void
    {
        $user = User::factory()->create();

        $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', [
                'token' => 'not-an-expo-token',
                'device_id' => '',
                'platform' => 'windows',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'device_id', 'platform']);
    }

    public function test_user_can_revoke_their_own_token(): void
    {
        $owner = User::factory()->create();
        $pushToken = $this->registerToken($owner, 'ExpoPushToken[revoke_token]');

        $this->mobileSession($owner)
            ->deleteJson("/api/v1/push-tokens/{$pushToken->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Token push revocato.');

        $this->assertModelMissing($pushToken);
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $pushToken = ExpoPushToken::query()->create([
            'user_id' => $owner->id,
            'token' => 'ExpoPushToken[private_token]',
            'token_hash' => hash('sha256', 'ExpoPushToken[private_token]'),
            'device_id' => 'private-device',
            'platform' => 'android',
            'last_registered_at' => now(),
        ]);

        $this->mobileSession($otherUser)
            ->deleteJson("/api/v1/push-tokens/{$pushToken->id}")
            ->assertNotFound();

        $this->assertModelExists($pushToken);
    }

    public function test_push_token_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[guest_token]'))
            ->assertUnauthorized();
    }

    private array $accessTokens = [];

    private function mobileSession(User $user): static
    {
        if (! isset($this->accessTokens[$user->id])) {
            $sessionId = Str::uuid()->toString();
            $this->accessTokens[$user->id] = $user->createToken('mobile-access:'.$sessionId, ['access'], now()->addHour())->plainTextToken;
            $user->createToken('mobile-refresh:'.$sessionId, ['refresh'], now()->addDay());
        }

        $this->app['auth']->forgetGuards();

        return $this->withToken($this->accessTokens[$user->id]);
    }

    public function test_registration_rejects_an_access_token_without_a_paired_mobile_session(): void
    {
        $user = User::factory()->create();
        $access = $user->createToken('mobile-access:'.Str::uuid(), ['access'], now()->addHour())->plainTextToken;

        $this->withToken($access)->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[no_session]'))
            ->assertUnauthorized();

        $this->assertDatabaseCount('expo_push_tokens', 0);
    }

    public function test_registration_rejects_a_refresh_session_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $sessionId = Str::uuid()->toString();
        $access = $user->createToken('mobile-access:'.$sessionId, ['access'], now()->addHour())->plainTextToken;
        $other->createToken('mobile-refresh:'.$sessionId, ['refresh'], now()->addDay());

        $this->withToken($access)->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[other_session]'))
            ->assertUnauthorized();

        $this->assertDatabaseCount('expo_push_tokens', 0);
    }

    public function test_registration_rejects_an_expired_refresh_session(): void
    {
        $user = User::factory()->create();
        $sessionId = Str::uuid()->toString();
        $access = $user->createToken('mobile-access:'.$sessionId, ['access'], now()->addHour())->plainTextToken;
        $user->createToken('mobile-refresh:'.$sessionId, ['refresh'], now()->subMinute());

        $this->withToken($access)->postJson('/api/v1/push-tokens', $this->payload('ExpoPushToken[expired_session]'))
            ->assertUnauthorized();

        $this->assertDatabaseCount('expo_push_tokens', 0);
    }

    public function test_registration_does_not_accept_client_supplied_session_identifier(): void
    {
        $user = User::factory()->create();
        $response = $this->mobileSession($user)->postJson('/api/v1/push-tokens', [
            ...$this->payload('ExpoPushToken[session_owned]'),
            'auth_session_id' => Str::uuid()->toString(),
        ])->assertCreated();

        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($this->accessTokens[$user->id]);
        $this->assertSame(Str::after($token->name, ':'), ExpoPushToken::query()->findOrFail($response->json('data.id'))->auth_session_id);
    }

    /** @return array<string, string> */
    private function payload(string $token): array
    {
        return [
            'token' => $token,
            'device_id' => 'device-123',
            'platform' => 'ios',
            'device_name' => 'iPhone di test',
            'app_version' => '1.0.0',
        ];
    }

    private function registerToken(User $user, string $token): ExpoPushToken
    {
        $response = $this->mobileSession($user)
            ->postJson('/api/v1/push-tokens', $this->payload($token))
            ->assertCreated();

        return ExpoPushToken::query()->findOrFail($response->json('data.id'));
    }
}
