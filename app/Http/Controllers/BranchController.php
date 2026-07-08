<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Traits\Sortable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    use AuthorizesRequests;
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'name' => 'name',
        'city' => 'city',
        'province' => 'province',
        'is_active' => 'is_active',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);

        $query = Branch::query();

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('province', 'like', $search);
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sort = $this->sortParams($request, $this->sortableColumns);
        $this->applySorting($query, $sort, $this->sortableColumns);

        return Inertia::render('Branches/Index', [
            'branches' => $query->get(),
            'filters' => array_merge(
                $request->only(['search', 'is_active']),
                ['sort' => $sort['sort'], 'direction' => $sort['direction']]
            ),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Branch::class);

        return Inertia::render('Branches/Create');
    }

    public function store(StoreBranchRequest $request)
    {
        Branch::create($request->validated());

        return redirect()->route('branches.index')
            ->with('success', 'Filiale creata.');
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return redirect()->route('branches.index')
            ->with('success', 'Filiale aggiornata.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Filiale eliminata.');
    }
}
