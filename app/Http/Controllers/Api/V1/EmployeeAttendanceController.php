<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Http\Requests\EmployeeAbsenceRequest;
use App\Http\Requests\ReviewEmployeeAbsenceRequest;
use App\Models\AttendanceSession;
use App\Models\EmployeeAbsence;
use App\Services\EmployeeAttendanceService;
use Illuminate\Http\JsonResponse;

class EmployeeAttendanceController extends Controller
{
    public function __construct(private EmployeeAttendanceService $attendance) {}

    public function index(AttendanceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->attendance->overview($request->user(), $request->validated())]);
    }

    public function reviewContext(AttendanceRequest $request, EmployeeAbsence $absence): \Illuminate\Http\JsonResponse
    {
        return response()->json(['data' => $this->attendance->reviewContext($request->user(), $absence)]);
    }

    public function approvers(\App\Http\Requests\UpdateEmployeeApproversRequest $request, \App\Models\User $user): JsonResponse
    {
        $this->attendance->setApprovers($request->user(), $user, $request->validated('approver_ids'));

        return response()->json(['message' => 'Referenti aggiornati.']);
    }

    public function export(AttendanceRequest $request): JsonResponse
    {
        $file = $this->attendance->export($request->user(), $request->validated());

        return response()->json(['data' => $file]);
    }

    public function clockIn(AttendanceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->attendance->clock($request->user(), true, $request->validated('note'))], 201);
    }

    public function clockOut(AttendanceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->attendance->clock($request->user(), false, null)]);
    }

    public function store(EmployeeAbsenceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->attendance->requestAbsence($request->user(), $request->validated())], 201);
    }

    public function update(EmployeeAbsenceRequest $request, EmployeeAbsence $absence): JsonResponse
    {
        return response()->json(['data' => $this->attendance->updateAbsence($request->user(), $absence, $request->validated())]);
    }

    public function cancel(AttendanceRequest $request, EmployeeAbsence $absence): JsonResponse
    {
        $this->attendance->cancel($request->user(), $absence);

        return response()->json(['message' => 'Richiesta annullata.']);
    }

    public function review(ReviewEmployeeAbsenceRequest $request, EmployeeAbsence $absence): JsonResponse
    {
        return response()->json(['data' => $this->attendance->review($request->user(), $absence, $request->validated())]);
    }

    public function manual(CorrectAttendanceRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->attendance->correct($request->user(), null, $request->validated())], 201);
    }

    public function correct(CorrectAttendanceRequest $request, AttendanceSession $session): JsonResponse
    {
        return response()->json(['data' => $this->attendance->correct($request->user(), $session, $request->validated())]);
    }
}
