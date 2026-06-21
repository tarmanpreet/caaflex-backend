<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branches.view-any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.update');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.delete') && Branch::count() > 1;
    }
}
