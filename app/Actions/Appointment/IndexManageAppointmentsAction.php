<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IndexManageAppointmentsAction
{
    public function execute(Request $request, User $user): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $request->integer('per_page', 20), 100));

        $query = Appointment::query()
            ->with([
                'client:id,first_name,last_name',
                'practiceType:id,name,color',
                'assignedUser:id,name',
            ]);

        $this->applyVisibilityScope($query, $user);
        $this->applyFilters($query, $request);

        return $query
            ->orderBy('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function applyVisibilityScope(Builder $query, User $user): void
    {
        if ($user->hasPermissionTo('appointments.view-any')) {
            return;
        }

        if ($user->hasPermissionTo('appointments.view-own')) {
            $query->where('assigned_user_id', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = '%' . $request->string('search')->trim() . '%';

            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('client', function (Builder $clientQuery) use ($search) {
                    $clientQuery
                        ->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhereRaw("concat(first_name, ' ', last_name) like ?", [$search]);
                })
                    ->orWhereHas('assignedUser', fn (Builder $userQuery) => $userQuery->where('name', 'like', $search))
                    ->orWhereHas('practiceType', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', $search));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_profile_id', $request->integer('client_id'));
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->integer('assigned_user_id'));
        }

        if ($request->filled('practice_type_id')) {
            $query->where('practice_type_id', $request->integer('practice_type_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('scheduled_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('scheduled_at', '<=', $request->date('to'));
        }
    }
}