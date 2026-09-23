<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogoutOtherSessionsAction
{
    public function execute(User $user, string $password, ?string $currentTokenId): int
    {
        $currentTokenName = $currentTokenId
            ? $user->tokens()->whereKey($currentTokenId)->value('name')
            : null;
        $pairedRefreshName = is_string($currentTokenName) && Str::startsWith($currentTokenName, 'mobile-access:')
            ? Str::replaceFirst('mobile-access:', 'mobile-refresh:', $currentTokenName)
            : null;

        $revokedTokens = $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->when($pairedRefreshName, fn ($query) => $query->where('name', '!=', $pairedRefreshName))
            ->delete();

        $guard = Auth::guard('web');
        $guard->setUser($user);
        $guard->logoutOtherDevices($password);

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->delete();
        }

        return $revokedTokens;
    }
}
