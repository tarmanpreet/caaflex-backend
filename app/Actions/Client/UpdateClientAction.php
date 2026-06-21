<?php

namespace App\Actions\Client;

use App\Models\ClientProfile;
use Illuminate\Support\Arr;

class UpdateClientAction
{
    public function execute(array $data, ClientProfile $client): ClientProfile
    {
        $client->update(Arr::except($data, ['create_account', 'account_email']));

        return $client;
    }
}
