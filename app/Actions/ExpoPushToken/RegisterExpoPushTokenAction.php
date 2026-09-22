<?php

namespace App\Actions\ExpoPushToken;

use App\Models\ExpoPushToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterExpoPushTokenAction
{
    /** @param array{token: string, device_id: string, platform: string, device_name?: ?string, app_version?: ?string} $data */
    public function execute(User $user, array $data): ExpoPushToken
    {
        $tokenHash = hash('sha256', $data['token']);

        return DB::transaction(function () use ($data, $tokenHash, $user): ExpoPushToken {
            $matchingToken = ExpoPushToken::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();
            $deviceToken = ExpoPushToken::query()
                ->whereBelongsTo($user)
                ->where('device_id', $data['device_id'])
                ->lockForUpdate()
                ->first();

            if ($matchingToken && $deviceToken && ! $matchingToken->is($deviceToken)) {
                $deviceToken->delete();
            }

            $pushToken = $matchingToken ?? $deviceToken ?? new ExpoPushToken;
            $pushToken->fill([
                ...$data,
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
                'last_registered_at' => now(),
            ])->save();

            return $pushToken->refresh();
        });
    }
}
