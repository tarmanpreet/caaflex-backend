<?php

namespace App\Actions\Client;

use App\Models\ClientProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class IndexClientAction
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = ClientProfile::query();

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('fiscal_code', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        return $query->with('user')->paginate(20)->withQueryString();
    }
}
