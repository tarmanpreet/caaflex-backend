<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PassportPkceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_client_can_authorize_refresh_and_revoke_pkce_tokens(): void
    {
        $user = User::factory()->create();
        $redirectUri = 'caaflex://oauth/callback';
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'CAA Flex Mobile Test',
            [$redirectUri],
            confidential: false,
        );
        $codeVerifier = Str::random(96);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');
        $state = Str::random(40);

        $authorizationResponse = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]));

        $authorizationResponse->assertOk();
        $authorizationResponse->assertSee('name="auth_token"', false);
        preg_match('/name="auth_token" value="([^"]+)"/', $authorizationResponse->getContent(), $matches);

        $approvalResponse = $this->actingAs($user)->post('/oauth/authorize', [
            'state' => $state,
            'client_id' => $client->getKey(),
            'auth_token' => $matches[1],
        ]);

        $approvalResponse->assertRedirect();
        parse_str(parse_url($approvalResponse->headers->get('Location'), PHP_URL_QUERY), $callbackQuery);
        $this->assertSame($state, $callbackQuery['state']);

        $tokenResponse = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirectUri,
            'code' => $callbackQuery['code'],
            'code_verifier' => $codeVerifier,
        ]);

        $tokenResponse->assertOk()->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token',
        ]);

        $refreshResponse = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'refresh_token' => $tokenResponse->json('refresh_token'),
            'scope' => '',
        ]);

        $refreshResponse->assertOk()->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token',
        ]);

        $this->withToken($refreshResponse->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $accessToken = Passport::token()->newQuery()
            ->where('user_id', $user->getKey())
            ->where('revoked', false)
            ->latest('created_at')
            ->firstOrFail();

        $this->withToken($refreshResponse->json('access_token'))
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertTrue($accessToken->fresh()->revoked);
        $this->assertTrue($accessToken->refreshToken->fresh()->revoked);
        $this->app['auth']->forgetGuards();
        $this->withToken($refreshResponse->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_pkce_exchange_rejects_an_invalid_code_verifier(): void
    {
        $user = User::factory()->create();
        $redirectUri = 'caaflex://oauth/callback';
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'CAA Flex Mobile Test',
            [$redirectUri],
            confidential: false,
        );
        $codeVerifier = Str::random(96);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $authorizationResponse = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => '',
            'state' => Str::random(40),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]));

        preg_match('/name="auth_token" value="([^"]+)"/', $authorizationResponse->getContent(), $matches);
        $approvalResponse = $this->actingAs($user)->post('/oauth/authorize', [
            'client_id' => $client->getKey(),
            'auth_token' => $matches[1],
        ]);
        parse_str(parse_url($approvalResponse->headers->get('Location'), PHP_URL_QUERY), $callbackQuery);

        $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirectUri,
            'code' => $callbackQuery['code'],
            'code_verifier' => Str::random(96),
        ])->assertBadRequest()->assertJsonPath('error', 'invalid_grant');
    }
}
