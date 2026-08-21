<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteManagedUserAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\PracticeType;
use App\Models\User;
use App\Traits\Sortable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests;
    use Sortable;

    /** @var array<string,string> */
    protected array $sortableColumns = [
        'name' => 'name',
        'email' => 'email',
        'open_practices_count' => 'open_practices_count',
        'is_active' => 'is_active',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles')->withCount([
            'assignedPractices',
            'assignedPractices as open_practices_count' => fn ($q) => $q->whereNotIn('status', ['completata', 'annullata']),
        ])->whereDoesntHave('roles', fn ($q) => $q->where('name', 'cliente'));

        if ($request->search) {
            $search = '%'.$request->search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        $sort = $this->sortParams($request, $this->sortableColumns);
        $this->applySorting($query, $sort, $this->sortableColumns);

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'sort' => $sort['sort'],
                'direction' => $sort['direction'],
            ],
            'canCreateUser' => $request->user()->can('create', User::class),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $assignableRoles = $request->user()->assignableRoles();

        return Inertia::render('Users/Create', [
            'assignableRoles' => $assignableRoles,
            'allPracticeTypes' => PracticeType::orderBy('name')->get(['id', 'name', 'color']),
            'branches' => Branch::active()->whereIn('id', $request->user()->accessibleBranchIds())->select('id', 'name', 'city', 'province')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action)
    {
        $user = $action->execute($request->validated());

        return redirect()->route('users.show', $user)->with('success', 'Utente creato.');
    }

    public function show(User $user, Request $request)
    {
        $this->authorize('view', $user);

        $user->load('roles');
        if ($user->hasRole('employee')) {
            $user->load(['practiceTypes', 'branches']);
        }

        $closedStatuses = ['completata', 'annullata'];

        $activeSearch = $request->active_search;
        $activeQuery = $user->assignedPractices()->with('client')->whereNotIn('status', $closedStatuses);
        if ($activeSearch) {
            $s = '%'.$activeSearch.'%';
            $activeQuery->where(fn ($q) => $q->where('type', 'like', $s)->orWhere('status', 'like', $s));
        }
        $activePractices = $activeQuery->orderByDesc('updated_at')->paginate(10, ['*'], 'active_page')->withQueryString();

        $closedSearch = $request->closed_search;
        $closedQuery = $user->assignedPractices()->with('client')->whereIn('status', $closedStatuses);
        if ($closedSearch) {
            $s = '%'.$closedSearch.'%';
            $closedQuery->where(fn ($q) => $q->where('type', 'like', $s)->orWhere('status', 'like', $s));
        }
        $closedPractices = $closedQuery->orderByDesc('updated_at')->paginate(10, ['*'], 'closed_page')->withQueryString();

        $roles = collect($request->user()->assignableRoles())
            ->push($user->roles->first()?->name)
            ->filter()
            ->unique()
            ->values();
        $allPracticeTypes = PracticeType::orderBy('name')->get(['id', 'name', 'color']);
        $branches = Branch::active()->whereIn('id', $request->user()->accessibleBranchIds())->select('id', 'name', 'city', 'province')->orderBy('name')->get();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'activePractices' => $activePractices,
            'closedPractices' => $closedPractices,
            'availableRoles' => $roles,
            'allPracticeTypes' => $allPracticeTypes,
            'branches' => $branches,
            'practiceFilters' => [
                'active_search' => $activeSearch,
                'closed_search' => $closedSearch,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action)
    {
        $action->execute($request->validated(), $user);

        return redirect()->route('users.show', $user)->with('success', 'Utente aggiornato.');
    }

    public function toggleActive(User $user)
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('users.show', $user)
            ->with('success', $user->is_active ? 'Utente attivato.' : 'Utente disattivato.');
    }

    public function destroy(User $user, Request $request, DeleteManagedUserAction $action)
    {
        abort_if($request->user()->is($user) || $user->hasRole('superadmin'), 403);
        $this->authorize('delete', $user);

        $action->execute($user);

        return redirect()->route('users.index')->with('success', 'Utente eliminato.');
    }
}
