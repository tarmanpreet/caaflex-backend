<?php

namespace App\Actions\PracticeDeadline;

use App\Http\Requests\ListPracticeDeadlinesRequest;
use App\Models\Branch;
use App\Models\PracticeDeadline;
use App\Models\User;
use App\Traits\Sortable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexPracticeDeadlineAction
{
    use Sortable;

    /** @var array<string, string> */
    protected array $sortableColumns = [
        'title' => 'title',
        'deadline_at' => 'deadline_at',
        'status' => 'status',
        'priority' => 'priority',
    ];

    public function execute(ListPracticeDeadlinesRequest $request, User $user): LengthAwarePaginator
    {
        $query = $this->baseScope($user);
        $this->applyFilters($query, $request);

        $sort = $this->sortParams($request, $this->sortableColumns, 'deadline_at');
        $this->applySorting($query, $sort, $this->sortableColumns);

        return $query
            ->with(['practice.client', 'practice.assignedUsers:id,name', 'assignee:id,name'])
            ->paginate(20)
            ->withQueryString()
            ->through(function (PracticeDeadline $deadline) use ($user): PracticeDeadline {
                $deadline->setAttribute('can_update', $user->can('updateDeadline', $deadline->practice));

                return $deadline;
            });
    }

    /** @return array{total: int, open: int, overdue: int, completed: int} */
    public function summary(User $user): array
    {
        $openStatuses = [PracticeDeadline::STATUS_PENDING, PracticeDeadline::STATUS_IN_PROGRESS];
        $stats = (clone $this->baseScope($user))
            ->selectRaw(
                'count(*) as total, '
                .'sum(case when status in (?, ?) then 1 else 0 end) as open_count, '
                .'sum(case when status in (?, ?) and deadline_at < ? then 1 else 0 end) as overdue_count, '
                .'sum(case when status = ? then 1 else 0 end) as completed_count',
                [
                    ...$openStatuses,
                    ...$openStatuses,
                    now(),
                    PracticeDeadline::STATUS_COMPLETED,
                ],
            )
            ->first();

        return [
            'total' => (int) $stats->total,
            'open' => (int) $stats->open_count,
            'overdue' => (int) $stats->overdue_count,
            'completed' => (int) $stats->completed_count,
        ];
    }

    private function baseScope(User $user): Builder
    {
        return PracticeDeadline::query()->whereHas('practice', function (Builder $query) use ($user): void {
            if (Branch::query()->exists()) {
                $query->whereIn('branch_id', $user->accessibleBranchIds());
            }

            if (! $user->hasPermissionTo('practices.view-any')) {
                $query->whereHas('assignedUsers', fn (Builder $assignedUsers) => $assignedUsers->where('users.id', $user->id));
            }
        });
    }

    private function applyFilters(Builder $query, ListPracticeDeadlinesRequest $request): void
    {
        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', $search)
                    ->orWhere('notes', 'like', $search)
                    ->orWhereHas('practice', function (Builder $practice) use ($search): void {
                        $practice->where('type', 'like', $search)
                            ->orWhereHas('client', function (Builder $client) use ($search): void {
                                $client->where('first_name', 'like', $search)
                                    ->orWhere('last_name', 'like', $search);
                            });
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->integer('priority'));
        }

        if ($request->string('timing')->toString() === 'overdue') {
            $query->overdue();
        }

        if ($request->string('timing')->toString() === 'upcoming') {
            $query->upcoming();
        }

        if ($request->string('timing')->toString() === 'open') {
            $query->whereIn('status', [PracticeDeadline::STATUS_PENDING, PracticeDeadline::STATUS_IN_PROGRESS]);
        }
    }
}
