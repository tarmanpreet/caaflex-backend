<?php

namespace App\Http\Controllers;

use App\Actions\User\UpdateUserAction;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\PracticeType;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

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

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => ['search' => $request->search],
        ]);
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

        $roles = Role::where('guard_name', 'web')->pluck('name');
        $allPracticeTypes = PracticeType::orderBy('name')->get(['id', 'name', 'color']);
        $branches = Branch::active()->select('id', 'name', 'city', 'province')->orderBy('name')->get();

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
}
