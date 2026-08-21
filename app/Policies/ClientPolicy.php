<?php

namespace App\Policies;

use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('clients.view-any');
    }

    public function view(User $user, ClientProfile $clientProfile): bool
    {
        return ($user->hasPermissionTo('clients.view-any') && $user->canAccessBranchId($clientProfile->branch_id)) ||
               ($user->hasPermissionTo('clients.view-own') && $clientProfile->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('clients.create');
    }

    public function update(User $user, ClientProfile $clientProfile): bool
    {
        return $user->canAccessBranchId($clientProfile->branch_id) && $user->hasPermissionTo('clients.update') &&
               ($user->hasRole('superadmin') || $user->hasRole('admin') || $clientProfile->created_by === $user->id);
    }

    public function delete(User $user, ClientProfile $clientProfile): bool
    {
        if (! $user->hasPermissionTo('clients.delete')) {
            return false;
        }

        if (! $user->canAccessBranchId($clientProfile->branch_id)) {
            return false;
        }

        // Superadmin can delete anyone; admin can delete employees and clients but NOT other admins
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            // Admins cannot delete other admins (only superadmin can)
            if ($clientProfile->user && $clientProfile->user->hasRole('admin')) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function uploadDocument(User $user, ClientProfile $clientProfile): bool
    {
        return $user->canAccessBranchId($clientProfile->branch_id) && $user->hasPermissionTo('documents.upload');
    }

    public function downloadDocument(User $user, ClientProfile $clientProfile, ClientDocument $document): bool
    {
        return $user->canAccessBranchId($clientProfile->branch_id) && $user->hasPermissionTo('documents.download') &&
               $document->client_profile_id === $clientProfile->id &&
               ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('employee') || $document->clientProfile->user_id === $user->id);
    }

    public function deleteDocument(User $user, ClientProfile $clientProfile, ClientDocument $document): bool
    {
        return $user->canAccessBranchId($clientProfile->branch_id)
            && $user->hasPermissionTo('documents.delete')
            && $document->client_profile_id === $clientProfile->id;
    }
}
