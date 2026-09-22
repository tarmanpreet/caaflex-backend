<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogoutOtherSessionsAction
{
    public function execute(User $user, string $password, ?string $currentTokenId): int
    {
        $revokedTokens = $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))
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
