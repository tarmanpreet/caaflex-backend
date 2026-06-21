<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\Models\User;
use App\Models\PracticeType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_name' => $user->roles->first()?->name,
                'is_active' => $user->is_active,
                'practice_types' => $user->practiceTypes
                    ->map(fn ($practiceType) => [
                        'id' => $practiceType->id,
                        'name' => $practiceType->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'message' => 'User created successfully.',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles')->withCount([
            'assignedPractices',
            'assignedPractices as open_practices_count' => fn($q) => $q->whereNotIn('status', ['completata', 'annullata']),
        ])->whereDoesntHave('roles', fn ($q) => $q->where('name', 'cliente'));

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return response()->json(['data' => $users]);
    }

    public function show(User $user, Request $request): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load('roles');
        if ($user->hasRole('employee')) {
            $user->load('practiceTypes');
        }

        $closedStatuses = ['completata', 'annullata'];

        $activeSearch = $request->active_search;
        $activeQuery  = $user->assignedPractices()->with('client')->whereNotIn('status', $closedStatuses);
        if ($activeSearch) {
            $s = '%' . $activeSearch . '%';
            $activeQuery->where(fn($q) => $q->where('type', 'like', $s)->orWhere('status', 'like', $s));
        }
        $activePractices = $activeQuery->orderByDesc('updated_at')->paginate(10, ['*'], 'active_page')->withQueryString();

        $closedSearch = $request->closed_search;
        $closedQuery  = $user->assignedPractices()->with('client')->whereIn('status', $closedStatuses);
        if ($closedSearch) {
            $s = '%' . $closedSearch . '%';
            $closedQuery->where(fn($q) => $q->where('type', 'like', $s)->orWhere('status', 'like', $s));
        }
        $closedPractices = $closedQuery->orderByDesc('updated_at')->paginate(10, ['*'], 'closed_page')->withQueryString();

        $roles            = Role::where('guard_name', 'web')->pluck('name');
        $allPracticeTypes = PracticeType::orderBy('name')->get(['id', 'name', 'color']);

        return response()->json([
            'data' => [
                'user'             => $user,
                'activePractices'  => $activePractices,
                'closedPractices'  => $closedPractices,
                'availableRoles'   => $roles,
                'allPracticeTypes' => $allPracticeTypes,
                'practiceFilters'  => [
                    'active_search' => $activeSearch,
                    'closed_search' => $closedSearch,
                ],
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $action->execute($request->validated(), $user);
        return response()->json(['message' => 'Utente aggiornato.', 'data' => $user]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $this->authorize('toggleActive', $user);
        $user->update(['is_active' => !$user->is_active]);
        $message = $user->is_active ? 'Utente attivato.' : 'Utente disattivato.';
        return response()->json(['message' => $message, 'data' => $user]);
    }
}
