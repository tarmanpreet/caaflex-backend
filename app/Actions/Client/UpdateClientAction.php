<?php

namespace App\Actions\Client;

use App\Models\ClientProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateClientAction
{
    public function execute(array $data, ClientProfile $client): ClientProfile
    {
        DB::transaction(function () use ($data, $client): void {
            $client->update(Arr::except($data, ['create_account', 'account_email']));

            if (array_key_exists('branch_id', $data)) {
                $client->practices()->update(['branch_id' => $data['branch_id']]);
                $client->appointments()->update(['branch_id' => $data['branch_id']]);
            }
        });

        return $client;
    }
}
