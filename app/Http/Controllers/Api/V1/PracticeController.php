<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Practice\IndexPracticeAction;
use App\Actions\Practice\StorePracticeAction;
use App\Actions\Practice\UpdatePracticeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Requests\UpdatePracticeRequest;
use App\Models\Practice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, IndexPracticeAction $action): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('viewAny', Practice::class) && !$user->hasPermissionTo('practices.view-own')) {
            abort(403);
        }

        $practices = $action->execute($request, $user);

        return response()->json($practices);
    }

    public function store(StorePracticeRequest $request, StorePracticeAction $action): JsonResponse
    {
        $practice = $action->execute($request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Practice created.',
            'data'    => $practice->load(['client', 'assignedUsers']),
        ], 201);
    }

    public function show(Practice $practice): JsonResponse
    {
        $this->authorize('view', $practice);

        $practice->load(['client', 'assignedUsers', 'notes.author', 'documents.uploader', 'statusLogs.user']);

        return response()->json([
            'data' => $practice,
        ]);
    }

    public function update(UpdatePracticeRequest $request, Practice $practice, UpdatePracticeAction $action): JsonResponse
    {
        $data = $request->validated();

        $practice = $action->execute($data, $practice, $request->user()->id);

        return response()->json([
            'message' => 'Practice updated.',
            'data'    => $practice->fresh(['client', 'assignedUsers']),
        ]);
    }

    public function destroy(Practice $practice): JsonResponse
    {
        $this->authorize('delete', $practice);

        $practice->delete();

        return response()->json([
            'message' => 'Practice deleted.',
        ]);
    }

    public function assignUsers(Request $request, Practice $practice): JsonResponse
    {
        $this->authorize('assign', $practice);

        $request->validate([
            'user_ids'   => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $practice->assignedUsers()->sync($request->user_ids);

        return response()->json([
            'message' => 'Users assigned.',
        ]);
    }
}
