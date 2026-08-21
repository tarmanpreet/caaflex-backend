<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view-any');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.view-any');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.update')
            && ! $model->hasRole('superadmin')
            && (! $model->hasRole('admin') || $user->hasPermissionTo('admins.create'));
    }

    public function toggleActive(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && $user->hasPermissionTo('users.update')
            && ! $model->hasRole('superadmin')
            && (! $model->hasRole('admin') || $user->hasPermissionTo('admins.create'));
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id || ! $user->hasPermissionTo('users.delete')) {
            return false;
        }

        if ($model->hasAnyRole(['superadmin', 'cliente'])) {
            return false;
        }

        return ! $model->hasRole('admin') || $user->hasPermissionTo('admins.delete');
    }
}
