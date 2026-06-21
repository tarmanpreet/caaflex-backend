<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeDeadlineRequest;
use App\Http\Requests\UpdatePracticeDeadlineRequest;
use App\Models\DeadlineReminder;
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

    public function store(StorePracticeDeadlineRequest $request, Practice $practice): JsonResponse
    {
        $this->authorize('createDeadline', $practice);

        $data = $request->validated();
        $data['practice_id'] = $practice->id;
        $data['created_by'] = $request->user('api')?->id ?? $request->user()->id;

        if (!isset($data['status'])) {
            $data['status'] = PracticeDeadline::STATUS_PENDING;
        }

        $deadline = PracticeDeadline::create($data);

        DeadlineReminder::create([
            'deadline_id' => $deadline->id,
            'type' => DeadlineReminder::TYPE_EMAIL,
            'minutes_before' => DeadlineReminder::MINUTES_DAY,
            'sent' => false,
        ]);

        DeadlineReminder::create([
            'deadline_id' => $deadline->id,
            'type' => DeadlineReminder::TYPE_IN_APP,
            'minutes_before' => DeadlineReminder::MINUTES_HOUR,
            'sent' => false,
        ]);

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

    public function update(UpdatePracticeDeadlineRequest $request, Practice $practice, PracticeDeadline $deadline): JsonResponse
    {
        $this->authorize('updateDeadline', $practice);

        $deadline->update($request->validated());

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