<?php

namespace App\Actions\PracticeDeadline;

use App\Models\Branch;
use App\Models\PracticeDeadline;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeVisiblePracticeDeadlineAction
{
    public function execute(User $user): Builder
    {
        $query = PracticeDeadline::query()
            ->whereHas('practice', function (Builder $practiceQuery) use ($user): void {
                if (Branch::query()->exists()) {
                    $practiceQuery->whereIn('branch_id', $user->accessibleBranchIds());
                }
            });

        if (! $user->hasPermissionTo('practice-deadlines.view')) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasPermissionTo('practices.view-any')) {
            return $query;
        }

        if ($user->hasPermissionTo('practices.view-own')) {
            return $query->where(function (Builder $deadlineQuery) use ($user): void {
                $deadlineQuery
                    ->where('user_id', $user->id)
                    ->orWhereHas('practice.assignedUsers', fn (Builder $assignedUsers) => $assignedUsers->where('users.id', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
