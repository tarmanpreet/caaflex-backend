<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAvailableSlotRequest;
use App\Http\Requests\FindAvailableSlotsRequest;
use App\Models\Appointment;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private AppointmentAvailabilityService $availability) {}

    public function options(Request $request): JsonResponse
    {
        $data = $request->validate(['client_profile_id' => ['nullable', 'integer', 'exists:client_profiles,id'], 'q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'], 'appointment_page' => ['nullable', 'integer', 'min:1']]);

        return response()->json(['data' => $this->availability->options($request->user(), $data)]);
    }

    public function slots(FindAvailableSlotsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->availability->slots($request->user(), $request->validated())]);
    }

    public function store(BookAvailableSlotRequest $request): JsonResponse
    {
        $appointment = $this->availability->book($request->user(), $request->validated('slot'), $request->validated('notes'));

        return response()->json(['data' => $appointment->only(['id', 'scheduled_at', 'duration_minutes', 'status'])], 201);
    }

    public function reschedule(BookAvailableSlotRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->availability->book($request->user(), $request->validated('slot'), $request->validated('notes'), $appointment);

        return response()->json(['data' => $appointment->only(['id', 'scheduled_at', 'duration_minutes', 'status'])]);
    }
}
