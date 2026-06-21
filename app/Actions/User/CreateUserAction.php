<?php

namespace App\Actions\User;

use App\Models\User;

class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        if ($data['role'] === 'employee') {
            $user->practiceTypes()->sync($data['practice_type_ids'] ?? []);
        }

        return $user->load(['roles', 'practiceTypes:id,name']);
    }
}