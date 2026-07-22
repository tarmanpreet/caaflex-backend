<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('appointments.view-any');
    }

    public function viewOwn(User $user): bool
    {
        return $user->hasPermissionTo('appointments.view-own');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->clientProfile?->id === $appointment->client_profile_id) {
            return true;
        }

        if (! $user->canAccessBranchId($appointment->branch_id)) {
            return false;
        }

        if ($user->hasPermissionTo('appointments.view-any')) {
            return true;
        }

        return $user->hasPermissionTo('appointments.view-own') && $appointment->assigned_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->canAccessBranchId($appointment->branch_id) && $user->hasPermissionTo('appointments.update');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->canAccessBranchId($appointment->branch_id) && $user->hasPermissionTo('appointments.delete');
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('appointments.assign');
    }
}
