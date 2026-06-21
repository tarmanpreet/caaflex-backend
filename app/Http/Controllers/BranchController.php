<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class BranchController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Branch::class);

        return Inertia::render('Branches/Index', [
            'branches' => Branch::orderBy('name')->get(),
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
