<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Models\Procedure;
use App\Models\PracticeType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ProcedureController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Procedure::class);

        return Inertia::render('Procedures/Index', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
            'procedures' => Procedure::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Procedure::class);

        return Inertia::render('Procedures/Create', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProcedureRequest $request)
    {
        Procedure::create($request->validated());

        return redirect()->route('procedures.index')
            ->with('success', 'Procedura creata.');
    }

    public function edit(Procedure $procedure)
    {
        $this->authorize('update', $procedure);

        return Inertia::render('Procedures/Edit', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
            'procedure' => $procedure,
        ]);
    }

    public function update(UpdateProcedureRequest $request, Procedure $procedure)
    {
        $procedure->update($request->validated());

        return redirect()->route('procedures.index')
            ->with('success', 'Procedura aggiornata.');
    }

    public function destroy(Procedure $procedure)
    {
        $this->authorize('delete', $procedure);

        $procedure->delete();

        return redirect()->route('procedures.index')
            ->with('success', 'Procedura eliminata.');
    }
}