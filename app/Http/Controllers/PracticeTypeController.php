<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeTypeRequest;
use App\Http\Requests\UpdatePracticeTypeRequest;
use App\Models\PracticeType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class PracticeTypeController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', PracticeType::class);

        return Inertia::render('PracticeTypes/Index', [
            'types' => PracticeType::orderBy('name')->get(),
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
