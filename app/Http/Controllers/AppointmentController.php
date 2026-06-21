<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\StoreAppointmentAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\AutoConfirmSlot;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('appointments.view-any') && ! $user->hasPermissionTo('appointments.view-own')) {
            abort(403);
        }

        $query = Appointment::query()->with(['client', 'assignedUser', 'practiceType', 'practice', 'branch']);

        if (! $user->hasPermissionTo('appointments.view-any')) {
            $query->where('assigned_user_id', $user->id);
        }

        if ($user->hasRole('employee')) {
            $branchIds = $user->branches()->pluck('branches.id');
            $query->where(function ($q) use ($branchIds) {
                $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from) {
            $query->where('scheduled_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->where('scheduled_at', '<=', $request->to);
        }

        if ($request->view === 'calendar') {
            $from = $request->from ?? now()->startOfMonth()->format('Y-m-d');
            $to = $request->to ?? now()->endOfMonth()->format('Y-m-d');

            $calendarAppointments = (clone $query)
                ->whereBetween('scheduled_at', [$from, $to.' 23:59:59'])
                ->get();

            $calendarEvents = $calendarAppointments->map(fn ($a) => $this->mapToCalendarEvent($a));

            $clients = ClientProfile::select('id', 'first_name', 'last_name')->orderBy('last_name')->get();
            $practiceTypes = PracticeType::orderBy('name')->get();
            $users = User::whereHas('availabilities')->where('is_active', true)->select('id', 'name')->orderBy('name')->get();
            $branches = Branch::active()->select('id', 'name', 'address', 'city', 'province', 'postal_code')->orderBy('name')->get();

            return Inertia::render('Appointments/Index', [
                'appointments' => null,
                'calendarEvents' => $calendarEvents,
                'filters' => $request->only(['status', 'from', 'to', 'view', 'branch_id']),
                'statuses' => Appointment::STATUSES,
                'clients' => $clients,
                'practiceTypes' => $practiceTypes,
                'users' => $users,
                'branches' => $branches,
                'autoConfirmSlots' => AutoConfirmSlot::orderBy('day_of_week')->orderBy('time_from')->get(),
            ]);
        }

        $appointments = $query->orderBy('scheduled_at', 'desc')->paginate(20)->withQueryString();
        $clients = ClientProfile::select('id', 'first_name', 'last_name')->orderBy('last_name')->get();
        $practiceTypes = PracticeType::orderBy('name')->get();
        $users = User::whereHas('availabilities')->where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $branches = Branch::active()->select('id', 'name', 'address', 'city', 'province', 'postal_code')->orderBy('name')->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['status', 'from', 'to', 'view', 'branch_id']),
            'statuses' => Appointment::STATUSES,
            'clients' => $clients,
            'practiceTypes' => $practiceTypes,
            'users' => $users,
            'branches' => $branches,
            'autoConfirmSlots' => AutoConfirmSlot::orderBy('day_of_week')->orderBy('time_from')->get(),
        ]);
    }

    public function store(StoreAppointmentRequest $request, StoreAppointmentAction $action)
    {
        $appointment = $action->execute($request->validated(), $request->user()->id);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appuntamento creato.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['client', 'assignedUser', 'practiceType', 'practice', 'creator']);

        $users = User::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
            'users' => $users,
            'statuses' => Appointment::STATUSES,
            'branches' => Branch::active()->select('id', 'name', 'address', 'city', 'province', 'postal_code')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, UpdateAppointmentAction $action)
    {
        $validated = $request->validated();

        // Preserve branch_id even if null (validation removes nullable fields)
        if ($request->has('branch_id')) {
            $validated['branch_id'] = $request->input('branch_id');
        }

        $action->execute($validated, $appointment, $request->user()->id);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appuntamento aggiornato.');
    }

    public function calendarEvents(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('appointments.view-any') && ! $user->hasPermissionTo('appointments.view-own')) {
            abort(403);
        }

        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ]);

        $query = Appointment::query()->with(['client', 'practiceType'])
            ->whereBetween('scheduled_at', [$request->from, $request->to.' 23:59:59']);

        if (! $user->hasPermissionTo('appointments.view-any')) {
            $query->where('assigned_user_id', $user->id);
        }

        if ($user->hasRole('employee')) {
            $branchIds = $user->branches()->pluck('branches.id');
            $query->where(function ($q) use ($branchIds) {
                $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        $events = $query->get()->map(fn ($a) => $this->mapToCalendarEvent($a));

        return response()->json($events);
    }

    public function practicesForModal(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $request->validate([
            'client_id' => ['required', 'integer', 'exists:client_profiles,id'],
            'practice_type_id' => ['required', 'integer', 'exists:practice_types,id'],
        ]);

        $practices = Practice::where('client_profile_id', $request->client_id)
            ->where('practice_type_id', $request->practice_type_id)
            ->select('id', 'type', 'status', 'reference_year')
            ->get();

        return response()->json($practices);
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
        ]);

        $appointment->update($request->only('scheduled_at', 'duration_minutes'));

        return response()->json(['ok' => true]);
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Appuntamento eliminato.');
    }

    private function mapToCalendarEvent(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->client->first_name.' '.$a->client->last_name
                                 .' - '.($a->practiceType?->name ?? ''),
            'start' => $a->scheduled_at->format('Y-m-d\TH:i:s'),
            'end' => $a->scheduled_at->copy()->addMinutes($a->duration_minutes)->format('Y-m-d\TH:i:s'),
            'backgroundColor' => $a->practiceType?->color ?? '#3B82F6',
            'borderColor' => $a->practiceType?->color ?? '#3B82F6',
            'extendedProps' => [
                'status' => $a->status,
                'duration_minutes' => $a->duration_minutes,
                'notes' => $a->notes,
                'client_id' => $a->client_profile_id,
                'practice_type_id' => $a->practice_type_id,
                'assigned_user_id' => $a->assigned_user_id,
                'practice_id' => $a->practice_id,
            ],
        ];
    }
}
