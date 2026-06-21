<?php

namespace App\Policies;

use App\Models\PracticeType;
use App\Models\User;

class PracticeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('practice-types.view-any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('practice-types.create');
    }

    public function update(User $user, PracticeType $practiceType): bool
    {
        return $user->hasPermissionTo('practice-types.update');
    }

    public function delete(User $user, PracticeType $practiceType): bool
    {
        return $user->hasPermissionTo('practice-types.delete');
    }
}
