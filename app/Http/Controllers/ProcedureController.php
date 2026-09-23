<?php

namespace App\Http\Controllers;

use App\Actions\Procedure\SyncProcedureDeadlineTemplatesAction;
use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Traits\Sortable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProcedureController extends Controller
{
    use AuthorizesRequests;
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'name' => 'name',
        'procedure_type_id' => 'procedure_type_id',
        'deadline_days' => 'deadline_days',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Procedure::class);

        $query = Procedure::query();

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('default_notes', 'like', $search);
            });
        }

        $sort = $this->sortParams($request, $this->sortableColumns);
        $this->applySorting($query, $sort, $this->sortableColumns);

        return Inertia::render('Procedures/Index', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
            'procedures' => $query->withCount('deadlineTemplates')->get(),
            'filters' => array_merge(
                $request->only(['search']),
                ['sort' => $sort['sort'], 'direction' => $sort['direction']]
            ),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Procedure::class);

        return Inertia::render('Procedures/Create', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProcedureRequest $request, SyncProcedureDeadlineTemplatesAction $syncTemplates): RedirectResponse
    {
        $data = $request->validated();
        $templates = Arr::pull($data, 'deadline_templates', []) ?? [];

        $procedure = DB::transaction(function () use ($data, $templates, $syncTemplates): Procedure {
            $procedure = Procedure::query()->create($data);
            $syncTemplates->execute($procedure, $templates);

            return $procedure;
        });

        return redirect()->route('procedures.edit', $procedure)
            ->with('success', 'Procedura creata.');
    }

    public function edit(Procedure $procedure)
    {
        $this->authorize('update', $procedure);

        return Inertia::render('Procedures/Edit', [
            'procedureTypes' => PracticeType::orderBy('name')->get(),
            'procedure' => $procedure->load('deadlineTemplates'),
        ]);
    }

    public function update(UpdateProcedureRequest $request, Procedure $procedure, SyncProcedureDeadlineTemplatesAction $syncTemplates): RedirectResponse
    {
        $data = $request->validated();
        $shouldSyncTemplates = $request->has('deadline_templates');
        $templates = Arr::pull($data, 'deadline_templates', []) ?? [];

        DB::transaction(function () use ($data, $procedure, $shouldSyncTemplates, $syncTemplates, $templates): void {
            $procedure->update($data);

            if ($shouldSyncTemplates) {
                $syncTemplates->execute($procedure, $templates);
            }
        });

        return back()
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
