<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeTypeRequest;
use App\Http\Requests\UpdatePracticeTypeRequest;
use App\Models\PracticeType;
use App\Traits\Sortable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PracticeTypeController extends Controller
{
    use AuthorizesRequests;
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'name' => 'name',
        'duration_minutes' => 'duration_minutes',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', PracticeType::class);

        $query = PracticeType::query();

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where('name', 'like', $search);
        }

        $sort = $this->sortParams($request, $this->sortableColumns);
        $this->applySorting($query, $sort, $this->sortableColumns);

        return Inertia::render('PracticeTypes/Index', [
            'types' => $query->get(),
            'filters' => array_merge(
                $request->only(['search']),
                ['sort' => $sort['sort'], 'direction' => $sort['direction']]
            ),
        ]);
    }

    public function create()
    {
        $this->authorize('create', PracticeType::class);

        return Inertia::render('PracticeTypes/Create');
    }

    public function store(StorePracticeTypeRequest $request)
    {
        PracticeType::create($request->validated());

        return redirect()->route('practice-types.index')
            ->with('success', 'Tipo pratica creato.');
    }

    public function edit(PracticeType $practice_type)
    {
        $this->authorize('update', $practice_type);

        return Inertia::render('PracticeTypes/Edit', [
            'type' => $practice_type,
        ]);
    }

    public function update(UpdatePracticeTypeRequest $request, PracticeType $practice_type)
    {
        $practice_type->update($request->validated());

        return redirect()->route('practice-types.index')
            ->with('success', 'Tipo pratica aggiornato.');
    }

    public function destroy(PracticeType $practice_type)
    {
        $this->authorize('delete', $practice_type);

        $practice_type->delete();

        return redirect()->route('practice-types.index')
            ->with('success', 'Tipo pratica eliminato.');
    }
}
