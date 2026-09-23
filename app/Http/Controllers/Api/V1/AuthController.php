<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;

class AuthController extends Controller
{
    private const ACCESS_TOKEN_PREFIX = 'mobile-access';

    private const REFRESH_TOKEN_PREFIX = 'mobile-refresh';

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->buildUserPayload($request->user()));
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Credenziali non valide.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account disattivato.'], 403);
        }

        if ($user->hasEnabledTwoFactorAuthentication() && ! $this->verifyTwoFactorCode($user, (string) $request->input('two_factor_code'))) {
            return response()->json([
                'message' => empty($request->input('two_factor_code'))
                    ? 'Richiesta verifica a due fattori.'
                    : 'Codice di verifica non valido.',
                'two_factor_required' => empty($request->input('two_factor_code')),
            ], 401);
        }

        $tokens = $this->issueTokens($user);

        return response()->json([
            'message' => 'Autenticazione riuscita.',
            'token_type' => 'Bearer',
            'access_token' => $tokens['access'],
            'refresh_token' => $tokens['refresh'],
            'expires_in' => config('sanctum.mobile.access_token_minutes') * 60,
            'user' => $this->buildUserPayload($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        [$id, $plain] = $this->splitToken($request->input('token'));

        return DB::transaction(function () use ($id, $plain): JsonResponse {
            $token = PersonalAccessToken::query()
                ->where('token', hash('sha256', $plain))
                ->when($id, fn ($query) => $query->whereKey($id))
                ->lockForUpdate()
                ->first();

            if (! $token
                || str_starts_with($token->name, self::REFRESH_TOKEN_PREFIX) === false
                || ! $token->can('refresh')
                || $this->isExpired($token)) {
                return response()->json(['message' => 'Token di refresh non valido.'], 401);
            }

            $user = $token->tokenable;

            if (! $user instanceof User || ! $user->is_active) {
                $token->delete();

                return response()->json(['message' => 'Token di refresh non valido.'], 401);
            }

            $token->delete();

            $tokens = $this->issueTokens($user);

            return response()->json([
                'token_type' => 'Bearer',
                'access_token' => $tokens['access'],
                'refresh_token' => $tokens['refresh'],
                'expires_in' => config('sanctum.mobile.access_token_minutes') * 60,
            ]);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        if (filled($request->input('refresh_token'))) {
            $refreshToken = PersonalAccessToken::findToken($request->input('refresh_token'));

            if ($refreshToken?->tokenable()->is($user) && $refreshToken->can('refresh')) {
                $refreshToken->delete();
            }
        }

        return response()->json(['message' => 'Disconnesso con successo.']);
    }

    public function emailPasswordReset(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $user = User::query()->where('email', $request->input('email'))->first();

        if ($user) {
            $code = Str::random(8);
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->updateOrInsert(
                ['email' => $user->getEmailForPasswordReset()],
                ['token' => Password::broker()->getRepository()->getHasher()->make($code), 'created_at' => now()],
            );

            $user->notify(new PasswordResetCode($code));
        }

        return response()->json(['message' => "Se l'indirizzo esiste, riceverai un'email con il codice di reset."]);
    }

    public function passwordReset(Request $request, ResetUserPassword $resetUserPassword): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', new PasswordRule(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'token', 'password', 'password_confirmation'),
            function (User $user, string $password) use ($resetUserPassword): void {
                $resetUserPassword->reset($user, [
                    'password' => $password,
                    'password_confirmation' => $password,
                ]);
                event(new PasswordResetEvent($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Codice di reset non valido o scaduto.'], 401);
        }

        return response()->json(['message' => 'Password reimpostata.']);
    }

    /** @return array<string, mixed> */
    private function buildUserPayload(User $user): array
    {
        $user->loadMissing(['roles:id,name', 'permissions:id,name', 'clientProfile:id,user_id']);
        $roles = $user->getRoleNames()->values();
        $roleName = $roles->first() ?? 'employee';
        $role = $roleName === 'cliente' ? 'citizenapp' : 'adminapp';
        $permissionNames = $user->getAllPermissions()->pluck('name')->sort()->values();
        $capabilities = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->reduce(function (array $capabilities, string $permission) use ($permissionNames): array {
                data_set($capabilities, $permission, $permissionNames->contains($permission));

                return $capabilities;
            }, []);
        $branches = Branch::query()
            ->whereIn('id', $user->accessibleBranchIds())
            ->select(['id', 'parent_id', 'name', 'city', 'province', 'is_active'])
            ->orderBy('name')
            ->get();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'client_profile_id' => $user->clientProfile?->id,
            'role' => $role,
            'role_name' => $roleName,
            'roles' => $roles,
            'permissions' => $permissionNames,
            'capabilities' => $capabilities,
            'branches' => $branches,
            'branding' => [
                'name' => config('branding.name'),
                'logo_url' => config('branding.logo_url'),
                'mark_url' => config('branding.mark_url'),
                'favicon_url' => config('branding.favicon_url'),
            ],
            'realtime' => [
                'enabled' => config('broadcasting.default') === 'reverb'
                    && filled(config('broadcasting.connections.reverb.key')),
                'broadcaster' => 'reverb',
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => (int) config('broadcasting.connections.reverb.options.port', 443),
                'scheme' => config('broadcasting.connections.reverb.options.scheme', 'https'),
            ],
            'features' => config('mobile.features'),
        ];
    }

    /** @return array<string, string> */
    private function issueTokens(User $user): array
    {
        $sessionId = Str::uuid()->toString();
        $access = $user->createToken(
            self::ACCESS_TOKEN_PREFIX.':'.$sessionId,
            ['access'],
            now()->addMinutes(config('sanctum.mobile.access_token_minutes')),
        );
        $refresh = $user->createToken(
            self::REFRESH_TOKEN_PREFIX.':'.$sessionId,
            ['refresh'],
            now()->addDays(config('sanctum.mobile.refresh_token_days')),
        );

        return [
            'access' => $access->plainTextToken,
            'refresh' => $refresh->plainTextToken,
        ];
    }

    private function verifyTwoFactorCode(User $user, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        if (app(TwoFactorAuthenticationProvider::class)->verify($secret, $code)) {
            return true;
        }

        $match = collect($user->recoveryCodes())
            ->first(fn (string $stored): bool => hash_equals($stored, $code));

        if ($match !== null) {
            $user->replaceRecoveryCode($match);
        }

        return $match !== null;
    }

    private function isExpired(PersonalAccessToken $token): bool
    {
        return $token->expires_at !== null && $token->expires_at->isPast();
    }

    /** @return array{0: int, 1: string} */
    private function splitToken(string $token): array
    {
        if (! str_contains($token, '|')) {
            return [0, $token];
        }

        [$id, $plain] = explode('|', $token, 2);

        return [ctype_digit($id) ? (int) $id : 0, $plain];
    }
}
