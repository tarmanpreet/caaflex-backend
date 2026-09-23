<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Practice\IndexPracticeAction;
use App\Actions\Practice\StorePracticeAction;
use App\Actions\Practice\UpdatePracticeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeRequest;
use App\Http\Requests\UpdatePracticeRequest;
use App\Models\Practice;
use App\Rules\AssignableUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, IndexPracticeAction $action): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('viewAny', Practice::class) && ! $user->hasPermissionTo('practices.view-own')) {
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
            'data' => $practice->load(['client', 'assignedUsers']),
        ], 201);
    }

    public function show(Practice $practice): JsonResponse
    {
        $this->authorize('view', $practice);

        $practice->load([
            'client',
            'assignedUsers',
            'notes.author',
            'documents.uploader',
            'statusLogs.user',
            'procedure.deadlineTemplates',
            'deadlines.assignee',
            'deadlines.reminders',
            'deadlines.steps',
            'branch',
            'practiceType',
        ]);

        return response()->json([
            'data' => $practice,
        ]);
    }

    public function showMine(Request $request, int $practice): JsonResponse
    {
        $clientProfile = $request->user()->clientProfile;

        if (! $clientProfile || ! $request->user()->hasPermissionTo('clients.view-own')) {
            abort(404);
        }

        $ownedPractice = $clientProfile->practices()
            ->with([
                'practiceType:id,name,color',
                'procedure:id,name',
                'branch:id,name',
                'statusLogs:id,practice_id,old_status,new_status,created_at',
            ])
            ->findOrFail($practice);

        return response()->json([
            'data' => [
                'id' => $ownedPractice->id,
                'type' => $ownedPractice->type,
                'status' => $ownedPractice->status,
                'tracking_code' => $ownedPractice->tracking_code,
                'reference_year' => $ownedPractice->reference_year,
                'deadline_at' => $ownedPractice->deadline_at,
                'created_at' => $ownedPractice->created_at,
                'updated_at' => $ownedPractice->updated_at,
                'practice_type' => $ownedPractice->practiceType,
                'procedure' => $ownedPractice->procedure,
                'branch' => $ownedPractice->branch,
                'status_history' => $ownedPractice->statusLogs->map(fn ($log) => [
                    'old_status' => $log->old_status,
                    'new_status' => $log->new_status,
                    'created_at' => $log->created_at,
                ]),
            ],
        ]);
    }

    public function update(UpdatePracticeRequest $request, Practice $practice, UpdatePracticeAction $action): JsonResponse
    {
        $data = $request->validated();

        if (! $request->user()->can('assign', $practice)) {
            unset($data['user_ids']);
        }

        $practice = $action->execute($data, $practice, $request->user()->id);

        return response()->json([
            'message' => 'Practice updated.',
            'data' => $practice->fresh(['client', 'assignedUsers']),
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

    public function assignUsers(Request $request, Practice $practice, UpdatePracticeAction $action): JsonResponse
    {
        $this->authorize('assign', $practice);

        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => [new AssignableUser],
        ]);

        $action->execute(['user_ids' => $request->input('user_ids')], $practice, $request->user()->id);

        return response()->json([
            'message' => 'Users assigned.',
        ]);
    }
}
