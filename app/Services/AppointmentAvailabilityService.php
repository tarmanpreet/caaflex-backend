<?php

namespace App\Services;

use App\Actions\Appointment\StoreAppointmentAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\PracticeType;
use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentAvailabilityService
{
    public function __construct(private FamilyAccessService $family) {}

    public function options(User $actor, array $filters = []): array
    {
        $clients = ClientProfile::query()->where(function ($q) use ($actor) {
            $q->where(function ($owned) use ($actor) {
                $owned->where('user_id', $actor->id)->whereRaw($actor->can('clients.view-own') ? '1=1' : '1=0');
            });
            $q->orWhereIn('id', \App\Models\ClientDelegation::activeFor($actor, 'book')->select('client_profile_id'));
            if ($actor->can('appointments.create') && $actor->can('clients.view-any')) {
                $q->orWhere(function ($staff) use ($actor) {
                    if (\App\Models\Branch::query()->exists()) {
                        $staff->whereIn('branch_id', $actor->accessibleBranchIds());
                    }
                });
            }
        })->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($names) => $names->where('first_name', 'like', '%'.addcslashes($term, '%_\\').'%')->orWhere('last_name', 'like', '%'.addcslashes($term, '%_\\').'%')))->orderBy('last_name')->paginate(30, ['id', 'first_name', 'last_name']);
        $client = isset($filters['client_profile_id']) ? ClientProfile::findOrFail($filters['client_profile_id']) : null;
        if ($client) {
            abort_unless($this->family->allows($actor, $client, 'book'), 403);
        }

        return ['clients' => $clients, 'practice_types' => PracticeType::query()->orderBy('name')->get(['id', 'name', 'duration_minutes']), 'client' => $client?->only(['id', 'first_name', 'last_name']), 'appointments' => $client ? Appointment::query()->where('client_profile_id', $client->id)->whereIn('status', ['da_confermare', 'confermato'])->where('scheduled_at', '>', now())->with('practiceType:id,name')->orderBy('scheduled_at')->paginate(20, ['id', 'client_profile_id', 'practice_type_id', 'scheduled_at', 'duration_minutes', 'status'], 'appointment_page') : null];
    }

    public function slots(User $actor, array $data): array
    {
        $client = ClientProfile::findOrFail($data['client_profile_id']);
        abort_unless($this->family->allows($actor, $client, 'book'), 403);
        $type = PracticeType::findOrFail($data['practice_type_id']);
        $duration = (int) $type->duration_minutes;
        if ($duration < 5 || $duration > 1440) {
            throw ValidationException::withMessages(['practice_type_id' => 'La durata della prestazione non è configurata.']);
        }
        $ignore = isset($data['appointment_id']) ? Appointment::findOrFail($data['appointment_id']) : null;
        if ($ignore) {
            $this->canReschedule($actor, $client, $ignore);
            if ($ignore->practice_type_id !== $type->id) {
                throw ValidationException::withMessages(['practice_type_id' => 'La riprogrammazione mantiene la prestazione originale.']);
            }
        }
        $day = Carbon::parse($data['date'])->startOfDay();
        $slots = [];
        $workers = User::query()->assignable()->where('is_active', true)->where(fn ($q) => $q->whereHas('practiceTypes', fn ($t) => $t->whereKey($type->id))->orWhereHas('roles', fn ($roles) => $roles->whereIn('name', ['admin', 'superadmin'])))->orderBy('name')->get();
        foreach ($workers as $worker) {
            if (! $worker->canAccessBranchId($client->branch_id)) {
                continue;
            }
            $intervals = UserAvailability::query()->where('user_id', $worker->id)->where('day_of_week', $day->dayOfWeek)->orderBy('time_from')->get();
            $appointments = Appointment::query()->where('assigned_user_id', $worker->id)->whereIn('status', ['da_confermare', 'confermato'])->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->where('scheduled_at', '<', $day->copy()->addDay())->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$day->toDateTimeString()])->get();
            $absences = \App\Models\EmployeeAbsence::query()->where('user_id', $worker->id)->whereIn('status', ['approved', 'reported'])->where('starts_at', '<', $day->copy()->addDay())->where('ends_at', '>', $day)->get();
            foreach ($intervals as $interval) {
                $start = $day->copy()->setTimeFromTimeString($interval->time_from);
                $end = $day->copy()->setTimeFromTimeString($interval->time_to);
                for ($cursor = $start; $cursor->copy()->addMinutes($duration)->lte($end); $cursor->addMinutes(15)) {
                    if ($cursor->lte(now()) || $absences->contains(fn ($a) => $a->starts_at->lt($cursor->copy()->addMinutes($duration)) && $a->ends_at->gt($cursor)) || $appointments->contains(fn ($a) => $a->scheduled_at->lt($cursor->copy()->addMinutes($duration)) && $a->scheduled_at->copy()->addMinutes($a->duration_minutes)->gt($cursor))) {
                        continue;
                    }
                    $payload = ['actor' => $actor->id, 'client_profile_id' => $client->id, 'practice_type_id' => $type->id, 'assigned_user_id' => $worker->id, 'scheduled_at' => $cursor->toDateTimeString(), 'duration_minutes' => $duration, 'expires' => now()->addMinutes(15)->timestamp, 'appointment_id' => $ignore?->id];
                    $key = $worker->id.'-'.$cursor->timestamp;
                    $slots[$key] = ['token' => Crypt::encryptString(json_encode($payload)), 'scheduled_at' => $cursor->toIso8601String(), 'duration_minutes' => $duration, 'operator' => ['id' => $worker->id, 'name' => $worker->name]];
                }
            }
        }

        return array_values($slots);
    }

    public function book(User $actor, string $token, ?string $notes = null, ?Appointment $appointment = null): Appointment
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['slot' => 'Orario non valido. Cerca di nuovo le disponibilità.']);
        }
        if (! is_array($data) || ($data['actor'] ?? null) !== $actor->id || ($data['expires'] ?? 0) < now()->timestamp || ($data['appointment_id'] ?? null) !== $appointment?->id) {
            throw ValidationException::withMessages(['slot' => 'La disponibilità è scaduta. Cerca di nuovo gli orari.']);
        }

        return DB::transaction(function () use ($actor, $data, $notes, $appointment) {
            $client = ClientProfile::query()->whereKey($data['client_profile_id'])->lockForUpdate()->firstOrFail();
            abort_unless($this->family->allows($actor, $client, 'book'), 403);
            User::query()->whereIn('id', array_filter([$data['assigned_user_id'], $appointment?->assigned_user_id]))->orderBy('id')->lockForUpdate()->get();
            $worker = User::findOrFail($data['assigned_user_id']);
            $type = PracticeType::findOrFail($data['practice_type_id']);
            if (! $worker->is_active || ! $worker->canAccessBranchId($client->branch_id) || ! $worker->hasAnyRole(['employee', 'admin', 'superadmin']) || (! $worker->hasAnyRole(['admin', 'superadmin']) && ! $worker->practiceTypes()->whereKey($type->id)->exists()) || (int) $type->duration_minutes !== $data['duration_minutes']) {
                throw ValidationException::withMessages(['slot' => 'La disponibilità è cambiata. Cerca di nuovo gli orari.']);
            }
            $start = Carbon::parse($data['scheduled_at']);
            $within = UserAvailability::query()->where('user_id', $worker->id)->where('day_of_week', $start->dayOfWeek)->where('time_from', '<=', $start->format('H:i:s'))->where('time_to', '>=', $start->copy()->addMinutes($data['duration_minutes'])->format('H:i:s'))->exists();
            if ($start->lte(now()) || ! $within) {
                throw ValidationException::withMessages(['slot' => 'Orario non più disponibile.']);
            }
            if ($appointment) {
                $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                $this->canReschedule($actor, $client, $appointment);
                if ($appointment->practice_type_id !== $type->id) {
                    throw ValidationException::withMessages(['slot' => 'La prestazione è cambiata. Cerca di nuovo le disponibilità.']);
                }
            }
            $values = collect($data)->only(['client_profile_id', 'practice_type_id', 'assigned_user_id', 'scheduled_at', 'duration_minutes'])->all() + ['branch_id' => $client->branch_id, 'notes' => $notes];
            if ($appointment) {
                $values['status'] = 'da_confermare';

                return app(UpdateAppointmentAction::class)->execute($values, $appointment, $actor->id);
            }

            return app(StoreAppointmentAction::class)->execute($values, $actor->id);
        });
    }

    private function canReschedule(User $actor, ClientProfile $client, Appointment $appointment): void
    {
        abort_unless($appointment->client_profile_id === $client->id && ($this->family->allows($actor, $client, 'book') || $actor->can('update', $appointment)), 403);
        if (! in_array($appointment->status, ['da_confermare', 'confermato'], true) || $appointment->scheduled_at->lte(now())) {
            throw ValidationException::withMessages(['appointment_id' => 'Questo appuntamento non può essere riprogrammato.']);
        }
    }

    public function guardOverlap(array $data, ?int $ignore = null): void
    {
        if (empty($data['assigned_user_id']) || empty($data['scheduled_at']) || in_array($data['status'] ?? null, ['cancellato', 'completato'], true)) {
            return;
        }
        User::query()->whereKey($data['assigned_user_id'])->lockForUpdate()->firstOrFail();
        $start = Carbon::parse($data['scheduled_at']);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);
        if (\App\Models\EmployeeAbsence::query()->where('user_id', $data['assigned_user_id'])->whereIn('status', ['approved', 'reported'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
            throw ValidationException::withMessages(['assigned_user_id' => 'L’operatore è assente in questo intervallo. Scegli un altro operatore.']);
        }
        $conflict = Appointment::query()->where('assigned_user_id', $data['assigned_user_id'])->whereIn('status', ['da_confermare', 'confermato'])->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->where('scheduled_at', '<', $end)->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$start->toDateTimeString()])->lockForUpdate()->first(['id']) !== null;
        if ($conflict) {
            throw ValidationException::withMessages(['scheduled_at' => 'L’operatore ha già un appuntamento in questo intervallo. Scegli un altro orario.']);
        }
    }
}
