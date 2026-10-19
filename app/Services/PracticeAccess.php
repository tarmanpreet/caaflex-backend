<?php

namespace App\Services;

use App\Models\Practice;
use App\Models\User;

class PracticeAccess
{
    public function staffQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Practice::query()->where(function ($staff) use ($user) {
            if (! $user->can('practices.view-any') && ! $user->can('practices.view-own')) {
                $staff->whereRaw('1=0');

                return;
            }
            if (\App\Models\Branch::query()->exists()) {
                $staff->whereIn('branch_id', $user->accessibleBranchIds());
            }
            if (! $user->can('practices.view-any')) {
                $staff->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user->id));
            }
        });
    }

    public function query(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Practice::query()->where(function ($visible) use ($user) {
            $visible->whereIn('id', $this->staffQuery($user)->select('practices.id'));
            $visible->orWhereIn('client_profile_id', \App\Models\ClientDelegation::activeFor($user)->select('client_profile_id'));
            if ($user->can('clients.view-own')) {
                $visible->orWhereHas('client', fn ($q) => $q->where('user_id', $user->id));
            }
        });
    }

    public function staff(User $user, Practice $practice): bool
    {
        return $user->can('view', $practice);
    }

    public function client(User $user, Practice $practice): bool
    {
        return ($user->can('clients.view-own') && $practice->client()->where('user_id', $user->id)->exists()) || \App\Models\ClientDelegation::activeFor($user)->where('client_profile_id', $practice->client_profile_id)->exists();
    }

    public function view(User $user, Practice $practice): bool
    {
        return $this->staff($user, $practice) || $this->client($user, $practice);
    }

    public function message(User $user, Practice $practice): bool
    {
        return $this->staff($user, $practice) || $this->owns($user, $practice) || \App\Models\ClientDelegation::activeFor($user, 'message')->where('client_profile_id', $practice->client_profile_id)->exists();
    }

    public function upload(User $user, Practice $practice): bool
    {
        return ($this->staff($user, $practice) && $user->can('uploadDocument', $practice)) || $this->owns($user, $practice) || \App\Models\ClientDelegation::activeFor($user, 'upload')->where('client_profile_id', $practice->client_profile_id)->exists();
    }

    public function documents(User $user, Practice $practice): bool
    {
        return ($this->staff($user, $practice) && $user->can('practice-documents.download')) || $this->owns($user, $practice) || \App\Models\ClientDelegation::activeFor($user, 'documents')->where('client_profile_id', $practice->client_profile_id)->exists();
    }

    private function owns(User $user, Practice $practice): bool
    {
        return $user->can('clients.view-own') && $practice->client()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, Practice $practice): bool
    {
        return $this->staff($user, $practice) && $user->can('update', $practice);
    }
}
