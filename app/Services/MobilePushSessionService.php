<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class MobilePushSessionService
{
    public function sessionId(?string $name, string $prefix = 'mobile-access'): ?string
    {
        if (! $name || ! str_starts_with($name, $prefix.':')) {
            return null;
        }

        $sessionId = Str::after($name, $prefix.':');

        return Str::isUuid($sessionId) ? $sessionId : null;
    }

    public function currentSessionId(User $user): ?string
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->can('access')) {
            return null;
        }

        $sessionId = $this->sessionId($token->name);

        return $sessionId && in_array($sessionId, $this->activeSessionIds($user), true) ? $sessionId : null;
    }

    /** @return array<int, string> */
    public function activeSessionIds(User $user): array
    {
        return $user->tokens()
            ->where('name', 'like', 'mobile-refresh:%')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->get()
            ->filter(fn (PersonalAccessToken $token): bool => $token->can('refresh'))
            ->map(fn (PersonalAccessToken $token): ?string => $this->sessionId($token->name, 'mobile-refresh'))
            ->filter()
            ->values()
            ->all();
    }
}
