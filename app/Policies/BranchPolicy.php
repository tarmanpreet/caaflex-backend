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
        return $user->hasPermissionTo('branches.update') && $user->canAccessBranchId($branch->id);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.delete')
            && $user->canAccessBranchId($branch->id)
            && $user->canAccessBranchId($branch->parent_id)
            && $branch->parent_id !== null
            && ! $branch->children()->exists();
    }
}
