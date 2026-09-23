<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_user_can_login_and_use_the_issued_tokens(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'refresh_token',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role', 'role_name', 'roles', 'permissions', 'branches'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertSame(['access'], PersonalAccessToken::findToken($response->json('access_token'))?->abilities);
        $this->assertSame(['refresh'], PersonalAccessToken::findToken($response->json('refresh_token'))?->abilities);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_bootstrap_exposes_the_linked_client_profile_identifier(): void
    {
        $user = User::factory()->create();
        $profile = ClientProfile::factory()->forUser($user)->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('client_profile_id', $profile->id);
    }

    public function test_login_rejects_invalid_credentials_without_issuing_tokens(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password-01',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Credenziali non valide.');

        $this->postJson('/api/v1/login', [
            'email' => 'missing@example.test',
            'password' => 'whatever-pass-01',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_an_inactive_user(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => false]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_login_validates_credentials(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_a_two_factor_code(): void
    {
        $user = $this->userWithTwoFactor();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnauthorized()
            ->assertJsonPath('two_factor_required', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $code = $this->twoFactorCode($user->fresh());

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'two_factor_code' => $code,
        ])->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    public function test_login_rejects_a_wrong_two_factor_code(): void
    {
        $user = $this->userWithTwoFactor();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'two_factor_code' => '000000',
        ])->assertUnauthorized()
            ->assertJsonPath('two_factor_required', false);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_two_factor_recovery_code_can_be_used_only_once(): void
    {
        $user = $this->userWithTwoFactor();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'two_factor_code' => 'abcd1234',
        ])->assertOk();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'two_factor_code' => 'abcd1234',
        ])->assertUnauthorized()
            ->assertJsonPath('two_factor_required', false);
    }

    public function test_refresh_token_rotates_the_pair_and_revokes_the_used_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $refreshed = $this->postJson('/api/v1/tokens/refresh', [
            'token' => $response->json('refresh_token'),
        ])->assertOk()
            ->assertJsonStructure(['token_type', 'access_token', 'refresh_token', 'expires_in']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => $this->hashedToken($response->json('refresh_token')),
        ]);

        $this->withToken($refreshed->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->postJson('/api/v1/tokens/refresh', [
            'token' => $refreshed->json('refresh_token'),
        ])->assertOk();
    }

    public function test_refresh_token_cannot_authenticate_protected_api_routes(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken($response->json('refresh_token'))
            ->getJson('/api/v1/me')
            ->assertForbidden();
    }

    public function test_an_existing_token_is_rejected_and_revoked_when_the_user_is_inactive(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('mobile-access', ['access'], now()->addHour())->plainTextToken;
        $user->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Account disattivato.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_refresh_rejects_invalid_and_expired_tokens(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/tokens/refresh', ['token' => 'bogus-token-value'])
            ->assertUnauthorized();

        $expired = $user->createToken('mobile-refresh', ['refresh'], now()->subDay())->plainTextToken;

        $this->postJson('/api/v1/tokens/refresh', ['token' => $expired])
            ->assertUnauthorized();
    }

    public function test_refresh_rejects_malformed_requests_and_access_tokens(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/tokens/refresh', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $accessToken = $user->createToken('mobile-access', ['access'], now()->addHour())->plainTextToken;

        $this->postJson('/api/v1/tokens/refresh', ['token' => $accessToken])
            ->assertUnauthorized();
    }

    public function test_logout_revokes_the_access_and_the_refresh_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken($response->json('access_token'))
            ->postJson('/api/v1/logout', [
                'refresh_token' => $response->json('refresh_token'),
            ])->assertOk()
            ->assertJsonPath('message', 'Disconnesso con successo.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($response->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertUnauthorized();

        $this->postJson('/api/v1/tokens/refresh', [
            'token' => $response->json('refresh_token'),
        ])->assertUnauthorized();
    }

    public function test_logout_does_not_revoke_another_users_refresh_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $otherUser = User::factory()->create();
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();
        $otherRefreshToken = $otherUser->createToken('mobile-refresh', ['refresh'], now()->addDay())->plainTextToken;

        $this->withToken($response->json('access_token'))
            ->postJson('/api/v1/logout', ['refresh_token' => $otherRefreshToken])
            ->assertOk();

        $this->assertNotNull(PersonalAccessToken::findToken($otherRefreshToken));
    }

    public function test_me_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_email_password_reset_sends_a_reset_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $sentCode = null;

        $this->postJson('/api/v1/email-password-reset', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'Se l\'indirizzo esiste, riceverai un\'email con il codice di reset.');

        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$sentCode): bool {
            $sentCode = $notification->code;

            return true;
        });

        $this->assertNotNull($sentCode);

        $this->postJson('/api/v1/email-password-reset', ['email' => 'missing@example.test'])
            ->assertOk();
    }

    public function test_email_password_reset_validates_the_email(): void
    {
        $this->postJson('/api/v1/email-password-reset', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_reset_their_password_with_the_sent_code(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'password']);
        $sentCode = null;

        $this->postJson('/api/v1/email-password-reset', ['email' => $user->email])->assertOk();

        $accessToken = $user->createToken('mobile-access', ['access'], now()->addHour())->plainTextToken;
        $refreshToken = $user->createToken('mobile-refresh', ['refresh'], now()->addDay())->plainTextToken;

        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$sentCode): bool {
            $sentCode = $notification->code;

            return true;
        });

        $this->postJson('/api/v1/password-reset', [
            'email' => $user->email,
            'token' => $sentCode,
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ])->assertOk()
            ->assertJsonPath('message', 'Password reimpostata.');

        $this->assertTrue(Hash::check('a-new-secure-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->withToken($accessToken)->getJson('/api/v1/me')->assertUnauthorized();
        $this->postJson('/api/v1/tokens/refresh', ['token' => $refreshToken])->assertUnauthorized();

        $this->postJson('/api/v1/password-reset', [
            'email' => $user->email,
            'token' => $sentCode,
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'a-new-secure-password',
        ])->assertOk();
    }

    public function test_password_reset_rejects_an_invalid_code(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/v1/password-reset', [
            'email' => $user->email,
            'token' => 'wrongcod',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Codice di reset non valido o scaduto.');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_reset_validates_the_payload(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/password-reset', [
            'email' => $user->email,
            'token' => 'abcdefgh',
            'password' => 'tooshort',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    private function hashedToken(string $token): string
    {
        return hash('sha256', str_contains($token, '|') ? explode('|', $token, 2)[1] : $token);
    }

    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create(['password' => 'password']);
        $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(
                array_merge(['abcd1234'], array_map(fn (int $index): string => sprintf('code-%04d', $index), range(1, 7))),
            )),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    private function twoFactorCode(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
        );
    }
}
