<?php

namespace App\Http\Controllers;

use App\Actions\Branch\DeleteBranchAction;
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

        $query = Branch::query()
            ->whereIn('id', $request->user()->accessibleBranchIds())
            ->with('parent:id,name')
            ->withCount(['children', 'employees', 'clients', 'practices', 'appointments']);

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
        $branches = $query->get();
        $branchLookup = Branch::query()
            ->whereIn('id', $request->user()->accessibleBranchIds())
            ->get(['id', 'parent_id', 'name'])
            ->keyBy('id');

        $branches->each(function (Branch $branch) use ($branchLookup): void {
            $depth = 0;
            $path = [$branch->name];
            $parentId = $branch->parent_id;
            $visited = [$branch->id];

            while ($parentId !== null && $branchLookup->has($parentId) && ! in_array($parentId, $visited, true)) {
                $parent = $branchLookup->get($parentId);
                array_unshift($path, $parent->name);
                $visited[] = $parentId;
                $parentId = $parent->parent_id;
                $depth++;
            }

            $branch->setAttribute('depth', $depth);
            $branch->setAttribute('hierarchy_path', implode(' / ', $path));
            $branch->setAttribute('is_main', $branch->parent_id === null);
        });

        $branches = $branches->sortBy('hierarchy_path', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters' => array_merge(
                $request->only(['search', 'is_active']),
                ['sort' => $sort['sort'], 'direction' => $sort['direction']]
            ),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Branch::class);

        return Inertia::render('Branches/Create', [
            'parentBranches' => Branch::active()
                ->whereIn('id', request()->user()->accessibleBranchIds())
                ->select('id', 'parent_id', 'name')
                ->orderBy('name')
                ->get(),
            'hasBranches' => Branch::query()->exists(),
        ]);
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
            'parentBranches' => Branch::active()
                ->whereIn('id', request()->user()->accessibleBranchIds())
                ->whereNotIn('id', Branch::descendantIdsFor([$branch->id]))
                ->select('id', 'parent_id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return redirect()->route('branches.index')
            ->with('success', 'Filiale aggiornata.');
    }

    public function destroy(Branch $branch, DeleteBranchAction $action)
    {
        $this->authorize('delete', $branch);

        $action->execute($branch);

        return redirect()->route('branches.index')
            ->with('success', 'Sede secondaria eliminata. I dati associati sono stati trasferiti alla sede padre.');
    }
}
