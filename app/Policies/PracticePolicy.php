<?php

namespace App\Policies;

use App\Models\Practice;
use App\Models\User;

class PracticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('practices.view-any');
    }

    public function view(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && (
            $user->hasPermissionTo('practices.view-any') ||
            ($user->hasPermissionTo('practices.view-own') && $practice->assignedUsers()->where('users.id', $user->id)->exists())
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('practices.create');
    }

    public function update(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practices.update') &&
               $this->isAdminOrAssigned($user, $practice);
    }

    public function delete(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practices.delete');
    }

    public function assign(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practices.assign');
    }

    public function uploadDocument(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-documents.upload');
    }

    public function downloadDocument(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-documents.download');
    }

    public function deleteDocument(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-documents.delete');
    }

    public function createNote(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-notes.create');
    }

    public function viewDeadline(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-deadlines.view') &&
               ($user->hasPermissionTo('practices.view-any') ||
                ($user->hasPermissionTo('practices.view-own') && $practice->assignedUsers()->where('users.id', $user->id)->exists()));
    }

    public function viewAnyDeadline(User $user): bool
    {
        return $user->hasPermissionTo('practice-deadlines.view')
            && ($user->hasPermissionTo('practices.view-any') || $user->hasPermissionTo('practices.view-own'));
    }

    public function createDeadline(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-deadlines.create') &&
               $this->isAdminOrAssigned($user, $practice);
    }

    public function updateDeadline(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-deadlines.update') &&
               $this->isAdminOrAssigned($user, $practice);
    }

    public function deleteDeadline(User $user, Practice $practice): bool
    {
        return $user->canAccessBranchId($practice->branch_id) && $user->hasPermissionTo('practice-deadlines.delete') && $user->hasRole('admin');
    }

    private function isAdminOrAssigned(User $user, Practice $practice): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($practice->relationLoaded('assignedUsers')) {
            return $practice->assignedUsers->contains('id', $user->id);
        }

        return $practice->assignedUsers()->where('users.id', $user->id)->exists();
    }
}
