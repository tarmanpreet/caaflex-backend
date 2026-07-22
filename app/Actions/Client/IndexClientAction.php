<?php

namespace App\Actions\Client;

use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\User;
use App\Traits\Sortable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class IndexClientAction
{
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'phone' => 'phone',
        'date_of_birth' => 'date_of_birth',
        'fiscal_code' => 'fiscal_code',
        'city' => 'city',
    ];

    public function execute(Request $request, User $user): LengthAwarePaginator
    {
        $query = ClientProfile::query();

        if (Branch::query()->exists()) {
            $query->whereIn('branch_id', $user->accessibleBranchIds());
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('fiscal_code', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('city', 'like', $search);
            });
        }

        $sort = $this->sortParams($request, $this->sortableColumns, 'last_name');
        $this->applySorting($query, $sort, $this->sortableColumns);

        // Secondary sort for stable ordering
        if ($sort['sort'] !== 'last_name') {
            $query->orderBy('last_name')->orderBy('first_name');
        } elseif ($sort['sort'] !== 'first_name') {
            $query->orderBy('first_name');
        }

        return $query->with(['user', 'branch:id,name'])->paginate(20)->withQueryString();
    }
}
