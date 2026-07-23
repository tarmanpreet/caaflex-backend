<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PracticeDeadline\StorePracticeDeadlineAction;
use App\Actions\PracticeDeadline\UpdatePracticeDeadlineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeDeadlineRequest;
use App\Http\Requests\UpdatePracticeDeadlineRequest;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PracticeDeadlineController extends Controller
{
    use AuthorizesRequests;

    public function index(Practice $practice): JsonResponse
    {
        $this->authorize('viewDeadline', $practice);

        $deadlines = $practice->deadlines()
            ->with(['assignee', 'reminders'])
            ->orderBy('deadline_at')
            ->get();

        return response()->json([
            'data' => $deadlines,
        ]);
    }

    public function store(StorePracticeDeadlineRequest $request, Practice $practice, StorePracticeDeadlineAction $action): JsonResponse
    {
        $this->authorize('createDeadline', $practice);

        $deadline = $action->execute($request->validated(), $practice, $request->user()->id);

        return response()->json([
            'message' => 'Deadline created.',
            'data' => $deadline->load(['assignee', 'reminders']),
        ], 201);
    }

    public function show(Practice $practice, PracticeDeadline $deadline): JsonResponse
    {
        $this->authorize('viewDeadline', $practice);

        return response()->json([
            'data' => $deadline->load(['assignee', 'reminders']),
        ]);
    }

    public function update(UpdatePracticeDeadlineRequest $request, Practice $practice, PracticeDeadline $deadline, UpdatePracticeDeadlineAction $action): JsonResponse
    {
        $this->authorize('updateDeadline', $practice);

        $action->execute($request->validated(), $practice, $deadline, $request->user()->id);

        return response()->json([
            'message' => 'Deadline updated.',
            'data' => $deadline->fresh(['assignee', 'reminders']),
        ]);
    }

    public function destroy(Practice $practice, PracticeDeadline $deadline): JsonResponse
    {
        $this->authorize('deleteDeadline', $practice);

        $deadline->delete();

        return response()->json([
            'message' => 'Deadline deleted.',
        ]);
    }
}
