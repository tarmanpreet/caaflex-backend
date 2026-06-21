<?php

namespace App\Actions\Practice;

use App\Models\Practice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class IndexPracticeAction
{
    public function execute(Request $request, User $user): LengthAwarePaginator
    {
        $query = Practice::query();

        if ($user->hasRole('employee') && ! $user->hasRole('admin') && ! $user->hasRole('superadmin')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user->id));
            $branchIds = $user->branches()->pluck('branches.id');
            $query->where(function ($q) use ($branchIds) {
                $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', $search)
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('reference_year')) {
            $query->where('reference_year', (int) $request->reference_year);
        }

        if ($request->filled('client_profile_id')) {
            $query->where('client_profile_id', (int) $request->client_profile_id);
        }

        return $query->with('client', 'assignedUsers')->paginate(20)->withQueryString();
    }
}
