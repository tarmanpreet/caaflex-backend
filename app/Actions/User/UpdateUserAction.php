<?php

namespace App\Actions\User;

use App\Models\User;

class UpdateUserAction
{
    public function execute(array $data, User $user): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles([$data['role']]);

        if ($data['role'] === 'employee') {
            $user->practiceTypes()->sync($data['practice_type_ids'] ?? []);
            $user->branches()->sync($data['branch_ids'] ?? []);
        } else {
            $user->practiceTypes()->detach();
            $user->branches()->detach();
        }

        return $user;
    }
}
