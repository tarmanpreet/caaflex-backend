<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Account\LogoutOtherSessionsAction;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Jetstream\DeleteUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmAccountPasswordRequest;
use App\Http\Requests\ConfirmTwoFactorAuthenticationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;

class AccountController extends Controller
{
    public function updateProfile(Request $request, UpdateUserProfileInformation $updateProfile): JsonResponse
    {
        $updateProfile->update($request->user(), $request->only('name', 'email'));

        return response()->json([
            'message' => 'Profilo aggiornato.',
            'data' => $this->profile($request->user()->fresh()),
        ]);
    }

    public function updatePassword(Request $request, UpdateUserPassword $updatePassword): JsonResponse
    {
        $updatePassword->update($request->user(), $request->only([
            'current_password',
            'password',
            'password_confirmation',
        ]));

        return response()->json(['message' => 'Password aggiornata.']);
    }

    public function twoFactorStatus(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->twoFactorData($request->user())]);
    }

    public function enableTwoFactor(
        ConfirmAccountPasswordRequest $request,
        EnableTwoFactorAuthentication $enableTwoFactor,
    ): JsonResponse {
        $this->ensureTwoFactorAuthenticationIsEnabled();
        $enableTwoFactor($request->user());

        return response()->json([
            'message' => 'Autenticazione a due fattori avviata.',
            'data' => $this->twoFactorData($request->user()->fresh()),
        ], 201);
    }

    public function confirmTwoFactor(
        ConfirmTwoFactorAuthenticationRequest $request,
        ConfirmTwoFactorAuthentication $confirmTwoFactor,
    ): JsonResponse {
        $this->ensureTwoFactorAuthenticationIsEnabled();
        $confirmTwoFactor($request->user(), $request->validated('code'));

        return response()->json([
            'message' => 'Autenticazione a due fattori confermata.',
            'data' => $this->twoFactorData($request->user()->fresh()),
        ]);
    }

    public function disableTwoFactor(
        ConfirmAccountPasswordRequest $request,
        DisableTwoFactorAuthentication $disableTwoFactor,
    ): JsonResponse {
        $this->ensureTwoFactorAuthenticationIsEnabled();
        $disableTwoFactor($request->user());

        return response()->json([
            'message' => 'Autenticazione a due fattori disattivata.',
            'data' => $this->twoFactorData($request->user()->fresh()),
        ]);
    }

    public function twoFactorQrCode(Request $request): JsonResponse
    {
        $this->ensureTwoFactorSetupExists($request->user());

        return response()->json(['data' => [
            'svg' => $request->user()->twoFactorQrCodeSvg(),
        ]]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $this->ensureTwoFactorSetupExists($request->user());

        return response()->json(['data' => [
            'codes' => $request->user()->recoveryCodes(),
        ]]);
    }

    public function regenerateRecoveryCodes(
        ConfirmAccountPasswordRequest $request,
        GenerateNewRecoveryCodes $generateRecoveryCodes,
    ): JsonResponse {
        $this->ensureTwoFactorSetupExists($request->user());
        $generateRecoveryCodes($request->user());

        return response()->json([
            'message' => 'Codici di recupero rigenerati.',
            'data' => ['codes' => $request->user()->fresh()->recoveryCodes()],
        ]);
    }

    public function logoutOtherSessions(
        ConfirmAccountPasswordRequest $request,
        LogoutOtherSessionsAction $logoutOtherSessions,
    ): JsonResponse {
        $revokedTokens = $logoutOtherSessions->execute(
            $request->user(),
            $request->validated('password'),
            $request->user()->currentAccessToken()?->getKey(),
        );

        return response()->json([
            'message' => 'Altre sessioni disconnesse.',
            'data' => ['revoked_tokens' => $revokedTokens],
        ]);
    }

    public function destroy(ConfirmAccountPasswordRequest $request, DeleteUser $deleteUser): JsonResponse
    {
        $request->user()->tokens()->delete();

        $deleteUser->delete($request->user());

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /** @return array<string, bool> */
    private function twoFactorData(User $user): array
    {
        return [
            'available' => Features::canManageTwoFactorAuthentication(),
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'setup_pending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
        ];
    }

    private function ensureTwoFactorAuthenticationIsEnabled(): void
    {
        abort_unless(Features::canManageTwoFactorAuthentication(), 404);
    }

    private function ensureTwoFactorSetupExists(User $user): void
    {
        $this->ensureTwoFactorAuthenticationIsEnabled();
        abort_if($user->two_factor_secret === null, 409, 'Two-factor authentication has not been enabled.');
    }
}
