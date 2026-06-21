<?php

namespace App\Policies;

use App\Models\Procedure;
use App\Models\User;

class ProcedurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('procedures.view-any');
    }

    public function view(User $user, Procedure $procedure): bool
    {
        return $user->hasPermissionTo('procedures.view-any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('procedures.create');
    }

    public function update(User $user, Procedure $procedure): bool
    {
        return $user->hasPermissionTo('procedures.update');
    }

    public function delete(User $user, Procedure $procedure): bool
    {
        return $user->hasPermissionTo('procedures.delete');
    }
}
