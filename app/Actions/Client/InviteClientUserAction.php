<?php

namespace App\Actions\Client;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Support\Str;

class InviteClientUserAction
{
    /**
     * Try to link or create a portal account for the given client profile.
     *
     * Returns one of three outcomes:
     *   - 'linked'  : an existing user without a profile was found and linked
     *   - 'created' : a new user was created and linked
     *   - 'conflict': the email belongs to a user that already has a different client profile
     */
    public function execute(ClientProfile $client, string $email): string
    {
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Check if this user already has a different client profile linked
            $hasOtherProfile = ClientProfile::where('user_id', $existingUser->id)
                ->where('id', '!=', $client->id)
                ->exists();

            if ($hasOtherProfile) {
                return 'conflict';
            }

            // User exists but has no other profile — just link it
            if (! $existingUser->hasRole('cliente')) {
                $existingUser->assignRole('cliente');
            }

            $client->update(['user_id' => $existingUser->id]);

            return 'linked';
        }

        // No user with this email — create a fresh one
        $user = User::create([
            'name' => $client->first_name.' '.$client->last_name,
            'email' => $email,
            'password' => bcrypt(Str::random(32)),
        ]);
        $user->assignRole('cliente');

        $client->update(['user_id' => $user->id]);

        return 'created';
    }
}
