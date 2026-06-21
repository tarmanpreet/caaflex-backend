<?php

namespace App\Actions\Client;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StoreClientAction
{
    public function execute(array $data, int $createdBy): ClientProfile
    {
        $userId = null;

        if (!empty($data['create_account'])) {
            $user = User::create([
                'name'     => $data['first_name'] . ' ' . $data['last_name'],
                'email'    => $data['account_email'],
                'password' => bcrypt(Str::random(32)),
            ]);
            $user->assignRole('cliente');
            $userId = $user->id;
        }

        return ClientProfile::create(
            Arr::except($data, ['create_account', 'account_email']) + [
                'user_id'    => $userId,
                'created_by' => $createdBy,
            ]
        );
    }
}
