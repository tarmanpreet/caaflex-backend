<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Appointment\IndexManageAppointmentsAction;
use App\Actions\Appointment\StoreAppointmentAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $client = auth()->user()->clientProfile;

        if (!$client) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('client_profile_id', $client->id)
            ->with(['assignedUser', 'practiceType', 'practice'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return response()->json($appointments);
    }

    public function manageIndex(Request $request, IndexManageAppointmentsAction $action): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('appointments.view-any') && ! $user->hasPermissionTo('appointments.view-own')) {
            abort(403);
        }

        $appointments = $action->execute($request, $user);
        $appointments->setCollection(
            $appointments->getCollection()->map(fn (Appointment $appointment) => [
                'id' => $appointment->id,
                'scheduled_at' => optional($appointment->scheduled_at)?->toIso8601String(),
                'duration_minutes' => $appointment->duration_minutes,
                'status' => $appointment->status,
                'client' => [
                    'id' => $appointment->client?->id,
                    'first_name' => $appointment->client?->first_name,
                    'last_name' => $appointment->client?->last_name,
                ],
                'practice_type' => [
                    'id' => $appointment->practiceType?->id,
                    'name' => $appointment->practiceType?->name,
                    'color' => $appointment->practiceType?->color,
                ],
                'assigned_user' => [
                    'id' => $appointment->assignedUser?->id,
                    'name' => $appointment->assignedUser?->name,
                ],
            ])
        );

        return response()->json($appointments);
    }

    public function store(StoreAppointmentRequest $request, StoreAppointmentAction $action): JsonResponse
    {
        $user = $request->user();
        $client = $user->clientProfile;

        if ($client && (int) $request->client_profile_id !== (int) $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment = $action->execute($request->validated(), (int) $user->id);

        return response()->json([
            'message' => 'Appuntamento creato.',
            'data' => $appointment->load(['client', 'assignedUser', 'practiceType', 'practice']),
        ], 201);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $client = auth()->user()->clientProfile;

        if (!$client || $appointment->client_profile_id !== $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($appointment->status === 'completato') {
            return response()->json(['message' => 'Impossibile cancellare un appuntamento completato.'], 422);
        }

        $appointment->update(['status' => 'cancellato']);

        return response()->json(['message' => 'Appuntamento cancellato.']);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        $appointment->load(['client', 'assignedUser', 'practiceType', 'practice', 'creator']);

        return response()->json([
            'data' => $appointment,
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, UpdateAppointmentAction $action): JsonResponse
    {
        $appointment = $action->execute($request->validated(), $appointment, $request->user()->id);

        return response()->json([
            'message' => 'Appuntamento aggiornato.',
            'data'    => $appointment->load(['client', 'assignedUser', 'practiceType', 'practice']),
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
        ]);

        $appointment->update($request->only('scheduled_at', 'duration_minutes'));

        return response()->json([
            'message' => 'Appuntamento riprogrammato.',
            'data'    => $appointment,
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('appointments.view-any') && !$user->hasPermissionTo('appointments.view-own')) {
            abort(403);
        }

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date'],
        ]);

        $query = Appointment::query()->with(['client', 'practiceType'])
            ->whereBetween('scheduled_at', [$request->from, $request->to . ' 23:59:59']);

        if (!$user->hasPermissionTo('appointments.view-any')) {
            $query->where('assigned_user_id', $user->id);
        }

        $events = $query->get()->map(fn($a) => [
            'id'              => $a->id,
            'title'           => $a->client->first_name . ' ' . $a->client->last_name
                                 . ' - ' . ($a->practiceType?->name ?? ''),
            'start'           => $a->scheduled_at->format('Y-m-d\TH:i:s'),
            'end'             => $a->scheduled_at->copy()->addMinutes($a->duration_minutes)->format('Y-m-d\TH:i:s'),
            'backgroundColor' => $a->practiceType?->color ?? '#3B82F6',
            'borderColor'     => $a->practiceType?->color ?? '#3B82F6',
            'extendedProps'   => [
                'status'           => $a->status,
                'duration_minutes' => $a->duration_minutes,
                'notes'            => $a->notes,
                'client_id'        => $a->client_profile_id,
                'practice_type_id' => $a->practice_type_id,
                'assigned_user_id' => $a->assigned_user_id,
                'practice_id'      => $a->practice_id,
            ],
        ]);

        return response()->json(['data' => $events]);
    }

    public function practicesForModal(Request $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $user = $request->user();
        $client = $user->clientProfile;

        $request->validate([
            'client_id'        => ['required', 'integer', 'exists:client_profiles,id'],
            'practice_type_id' => ['required', 'integer', 'exists:practice_types,id'],
        ]);

        if ($client && (int) $request->client_id !== (int) $client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $practices = Practice::where('client_profile_id', $request->client_id)
            ->where('practice_type_id', $request->practice_type_id)
            ->select('id', 'type', 'status', 'reference_year')
            ->get();

        return response()->json(['data' => $practices]);
    }

    public function availableUsers(): JsonResponse
    {
        $users = User::whereHas('availabilities')
            ->with('availabilities')
            ->where('is_active', true)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'availabilities' => $u->availabilities,
            ]);

        return response()->json(['data' => $users]);
    }

    public function practiceTypes(): JsonResponse
    {
        $types = PracticeType::orderBy('name')->get();

        return response()->json(['data' => $types]);
    }
}
