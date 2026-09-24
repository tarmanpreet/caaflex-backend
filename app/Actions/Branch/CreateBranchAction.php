<?php

namespace App\Actions\Branch;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use Illuminate\Support\Facades\DB;

class CreateBranchAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Branch
    {
        return DB::transaction(function () use ($attributes): Branch {
            $isFirstBranch = Branch::query()->doesntExist();
            $branch = Branch::query()->create($attributes);

            if ($isFirstBranch) {
                ClientProfile::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
                Practice::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
                Appointment::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
            }

            return $branch;
        });
    }
}
