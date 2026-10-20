<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if (\App\Models\AttendanceSession::query()->where('user_id', $user->id)->exists() || \App\Models\EmployeeAbsence::query()->where('user_id', $user->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user' => 'L’utente ha presenze o assenze nello storico. Disattiva l’account per conservare i registri.']);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
