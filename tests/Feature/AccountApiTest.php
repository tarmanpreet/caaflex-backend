<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user, 'api')
            ->putJson('/api/v1/account/profile', [
                'name' => 'Mario Rossi',
                'email' => 'mario.rossi@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Mario Rossi')
            ->assertJsonPath('data.email', 'mario.rossi@example.test');

        $this->actingAs($user->fresh(), 'api')
            ->putJson('/api/v1/account/password', [
                'current_password' => 'password',
                'password' => 'a-new-secure-password',
                'password_confirmation' => 'a-new-secure-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('a-new-secure-password', $user->fresh()->password));
    }

    public function test_current_password_is_required_for_sensitive_account_actions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson('/api/v1/account', ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertModelExists($user);
    }

    public function test_user_can_enable_confirm_and_disable_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/account/two-factor', ['password' => 'password'])
            ->assertCreated()
            ->assertJsonPath('data.setup_pending', true);

        $user->refresh();
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/account/two-factor/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->actingAs($user->fresh(), 'api')
            ->getJson('/api/v1/account/two-factor/qr-code')
            ->assertOk()
            ->assertJsonPath('data.svg', fn ($svg): bool => str_contains($svg, '<svg'));

        $codesResponse = $this->actingAs($user->fresh(), 'api')
            ->getJson('/api/v1/account/two-factor/recovery-codes')
            ->assertOk()
            ->assertJsonCount(8, 'data.codes');

        $originalCodes = $codesResponse->json('data.codes');

        $this->actingAs($user->fresh(), 'api')
            ->postJson('/api/v1/account/two-factor/recovery-codes', ['password' => 'password'])
            ->assertOk()
            ->assertJsonCount(8, 'data.codes')
            ->assertJsonMissing(['codes' => $originalCodes]);

        $this->actingAs($user->fresh(), 'api')
            ->deleteJson('/api/v1/account/two-factor', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_two_factor_setup_resources_require_an_active_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/account/two-factor/qr-code')
            ->assertConflict();
    }

    public function test_invalid_two_factor_code_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/account/two-factor', ['password' => 'password'])
            ->assertCreated();

        $this->actingAs($user->fresh(), 'api')
            ->postJson('/api/v1/account/two-factor/confirm', ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_logging_out_other_sessions_revokes_other_api_tokens(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current-token')->plainTextToken;
        $otherToken = $user->createToken('other-token')->plainTextToken;

        $this->withToken($currentToken)
            ->deleteJson('/api/v1/account/other-sessions', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 1);

        $this->assertDatabaseHas('personal_access_tokens', ['token' => $this->hashedToken($currentToken)]);
        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $this->hashedToken($otherToken)]);

        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_user_can_delete_own_account_with_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson('/api/v1/account', ['password' => 'password'])
            ->assertNoContent();

        $this->assertModelMissing($user);
    }

    private function hashedToken(string $token): string
    {
        return hash('sha256', str_contains($token, '|') ? explode('|', $token, 2)[1] : $token);
    }
}
