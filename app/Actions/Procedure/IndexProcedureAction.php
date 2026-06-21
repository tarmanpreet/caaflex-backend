<?php

namespace App\Actions\Procedure;

use App\Models\Procedure;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class IndexProcedureAction
{
    public function execute(Request $request, User $user): LengthAwarePaginator
    {
        if (!$user->hasPermissionTo('procedures.view-any')) {
            abort(403);
        }

        $query = Procedure::query();

        if ($request->filled('procedure_type_id')) {
            $query->where('procedure_type_id', (int) $request->procedure_type_id);
        }

        if ($request->filled('deadline_days')) {
            $query->where('deadline_days', (int) $request->deadline_days);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where('name', 'like', $search);
        }

        return $query->with('practiceType')->paginate(20)->withQueryString();
    }
}