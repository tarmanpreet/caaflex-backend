<?php

namespace App\Http\Controllers;

use App\Actions\Practice\IndexPracticeAction;
use App\Actions\Practice\StorePracticeAction;
use App\Actions\Practice\UpdatePracticeAction;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Requests\UpdatePracticeRequest;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use App\Traits\Sortable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PracticeController extends Controller
{
    use AuthorizesRequests;
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'id' => 'practices.id',
        'type' => 'type',
        'status' => 'status',
        'reference_year' => 'reference_year',
    ];

    public function index(Request $request, IndexPracticeAction $action)
    {
        $user = $request->user();

        if (! $user->can('viewAny', Practice::class) && ! $user->hasPermissionTo('practices.view-own')) {
            abort(403);
        }

        $practices = $action->execute($request, $user);
        $sort = $this->sortParams($request, $this->sortableColumns, 'id', 'desc');

        return Inertia::render('Practices/Index', [
            'practices' => $practices,
            'summary' => $action->summary($user),
            'filters' => array_merge(
                $request->only(['search', 'status', 'type', 'branch_id', 'reference_year']),
                ['sort' => $sort['sort'], 'direction' => $sort['direction']]
            ),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Practice::class);

        $branchIds = request()->user()->accessibleBranchIds();
        $clients = ClientProfile::select('id', 'first_name', 'last_name', 'branch_id')
            ->when(Branch::query()->exists(), fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->orderBy('last_name')
            ->get();

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'employee']))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Practices/Create', [
            'clients' => $clients,
            'users' => $users,
            'procedures' => Procedure::orderBy('name')->get(),
            'practiceTypes' => PracticeType::orderBy('name')->get(),
            'branches' => Branch::active()->whereIn('id', $branchIds)->select('id', 'parent_id', 'name', 'address', 'city', 'province', 'postal_code')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePracticeRequest $request, StorePracticeAction $action)
    {
        $practice = $action->execute($request->validated(), $request->user()->id);

        return redirect()->route('practices.show', $practice)
            ->with('success', 'Practice created successfully.');
    }

    public function show(Practice $practice)
    {
        $this->authorize('view', $practice);

        $notesText = $practice->notes;

        $practice->load(['client', 'assignedUsers', 'notes.author', 'documents.uploader', 'statusLogs.user', 'procedure', 'deadlines.assignee', 'deadlines.reminders', 'branch']);

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'employee']))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Practices/Show', [
            'practice' => $practice,
            'notes_text' => $notesText,
            'users' => $users,
            'procedures' => Procedure::orderBy('name')->get(),
            'practiceTypes' => PracticeType::orderBy('name')->get(),
            'procedure_id' => $practice->procedure_id,
            'branches' => Branch::active()->whereIn('id', request()->user()->accessibleBranchIds())->select('id', 'parent_id', 'name', 'address', 'city', 'province', 'postal_code')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePracticeRequest $request, Practice $practice, UpdatePracticeAction $action)
    {
        $data = $request->validated();

        if (! $request->user()->can('assign', $practice)) {
            unset($data['user_ids']);
        }

        // Preserve branch_id even if null (validation removes nullable fields)
        if ($request->has('branch_id')) {
            $data['branch_id'] = $request->input('branch_id');
        }

        $action->execute($data, $practice, $request->user()->id);

        return redirect()->route('practices.show', $practice)
            ->with('success', 'Practice updated successfully.');
    }

    public function destroy(Practice $practice)
    {
        $this->authorize('delete', $practice);

        $practice->delete();

        return redirect()->route('practices.index')
            ->with('success', 'Practice deleted.');
    }

    public function assignUsers(Request $request, Practice $practice, UpdatePracticeAction $action)
    {
        $this->authorize('assign', $practice);

        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $action->execute(
            ['user_ids' => $request->input('user_ids')],
            $practice,
            $request->user()->id,
        );

        return redirect()->back()
            ->with('success', 'Users assigned successfully.');
    }
}
