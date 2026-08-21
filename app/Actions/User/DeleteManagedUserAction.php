<?php

namespace App\Actions\User;

use App\Actions\Jetstream\DeleteUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteManagedUserAction
{
    public function __construct(private DeleteUser $deleteUser) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->notifications()->delete();
            $this->deleteUser->delete($user);
        });
    }
}
