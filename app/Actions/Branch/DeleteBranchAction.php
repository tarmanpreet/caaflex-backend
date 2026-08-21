<?php

namespace App\Actions\Branch;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use LogicException;

class DeleteBranchAction
{
    public function execute(Branch $branch): void
    {
        DB::transaction(function () use ($branch): void {
            $branch = Branch::query()->lockForUpdate()->findOrFail($branch->id);

            if ($branch->parent_id === null || $branch->children()->exists()) {
                throw new LogicException('La sede principale o una sede con sottosedi non può essere eliminata.');
            }

            $parent = Branch::query()->lockForUpdate()->findOrFail($branch->parent_id);
            $employeeIds = $branch->employees()->pluck('users.id');

            if ($employeeIds->isNotEmpty()) {
                $parent->employees()->syncWithoutDetaching($employeeIds);
            }

            $branch->clients()->update(['branch_id' => $parent->id]);
            $branch->practices()->update(['branch_id' => $parent->id]);
            $branch->appointments()->update(['branch_id' => $parent->id]);
            $branch->delete();
        });
    }
}
