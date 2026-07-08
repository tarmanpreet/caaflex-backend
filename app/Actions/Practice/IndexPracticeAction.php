<?php

namespace App\Actions\Practice;

use App\Models\Practice;
use App\Models\User;
use App\Traits\Sortable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IndexPracticeAction
{
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'id' => 'practices.id',
        'type' => 'type',
        'status' => 'status',
        'reference_year' => 'reference_year',
    ];

    public function execute(Request $request, User $user): LengthAwarePaginator
    {
        $query = $this->baseScope($user);
        $this->applyFilters($query, $request);

        $sort = $this->sortParams($request, $this->sortableColumns, 'id', 'desc');
        $this->applySorting($query, $sort, $this->sortableColumns);

        return $query->with('client', 'assignedUsers')->paginate(20)->withQueryString();
    }

    /** @return array{total:int,active:int,pending:int,complete:int} */
    public function summary(User $user): array
    {
        $stats = (clone $this->baseScope($user))
            ->selectRaw(
                'count(*) as total, '
                ."sum(case when status = 'in_lavorazione' then 1 else 0 end) as active, "
                ."sum(case when status = 'in_attesa_documenti' then 1 else 0 end) as pending, "
                ."sum(case when status = 'completata' then 1 else 0 end) as complete"
            )
            ->first();

        return [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'pending' => (int) $stats->pending,
            'complete' => (int) $stats->complete,
        ];
    }

    private function baseScope(User $user): Builder
    {
        $query = Practice::query();

        if ($user->hasRole('employee') && ! $user->hasRole('admin') && ! $user->hasRole('superadmin')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user->id));
            $branchIds = $user->branches()->pluck('branches.id');
            $query->where(function ($q) use ($branchIds) {
                $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        return $query;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', $search)
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('reference_year')) {
            $query->where('reference_year', (int) $request->reference_year);
        }

        if ($request->filled('client_profile_id')) {
            $query->where('client_profile_id', (int) $request->client_profile_id);
        }
    }
}
