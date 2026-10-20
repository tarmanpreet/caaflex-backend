<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\EmployeeAbsence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeAttendanceService
{
    public const TIMEZONE = 'Europe/Rome';

    public function isManager(User $actor): bool
    {
        return ! $actor->hasRole('cliente') && $actor->hasAnyRole(['admin', 'superadmin']);
    }

    public function staff(User $actor): Builder
    {
        $query = User::query()->assignable()->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'));
        if ($this->isManager($actor)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereKey($actor->id)->orWhereIn('id', $actor->supervisedEmployees()->whereDoesntHave('roles', fn (Builder $r) => $r->whereIn('name', ['admin', 'superadmin']))->select('users.id')));
    }

    public function canReview(User $actor, int $employeeId): bool
    {
        return $this->isManager($actor) || ($actor->id !== $employeeId && $actor->supervisedEmployees()->whereKey($employeeId)->exists());
    }

    private function authorizeTarget(User $actor, int $userId, bool $manage = false): User
    {
        abort_unless($actor->is_active && ! $actor->hasRole('cliente') && $actor->hasAnyRole(['employee', 'admin', 'superadmin']), 403);
        abort_if($manage && ! $this->isManager($actor), 403);

        return $this->staff($actor)->whereKey($userId)->firstOrFail();
    }

    public function overview(User $actor, array $filters): array
    {
        $target = $this->authorizeTarget($actor, (int) ($filters['user_id'] ?? $actor->id));
        $month = $filters['month'] ?? now(self::TIMEZONE)->format('Y-m');
        $from = CarbonImmutable::createFromFormat('!Y-m', $month, self::TIMEZONE)->startOfMonth()->utc();
        $until = $from->setTimezone(self::TIMEZONE)->addMonth()->utc();
        $sessions = AttendanceSession::query()->where('user_id', $target->id)->where('started_at', '<', $until)->where(fn (Builder $q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $from))->orderByDesc('started_at')->get();
        $absences = EmployeeAbsence::query()->where('user_id', $target->id)->where('starts_at', '<', $until)->where('ends_at', '>', $from)->orderByDesc('starts_at')->get();
        $pending = ($this->isManager($actor) || $actor->supervisedEmployees()->exists()) ? EmployeeAbsence::query()->with('user:id,name')->whereIn('user_id', $this->staff($actor)->select('users.id'))->whereIn('status', ['pending', 'reported'])->orderBy('starts_at')->get() : collect();
        $seconds = $sessions->sum(fn (AttendanceSession $s) => $s->ended_at ? max(0, $s->started_at->max($from)->diffInSeconds($s->ended_at->min($until), false)) : 0);

        if (! $this->isManager($actor) && $target->id !== $actor->id) {
            $sessions = collect();
            $seconds = 0;
        }

        return [
            'month' => $month, 'timezone' => self::TIMEZONE, 'target' => $target->only('id', 'name'), 'can_manage' => $this->isManager($actor),
            'staff' => $this->staff($actor)->orderBy('name')->get(['users.id', 'name', 'is_active']),
            'can_review' => $this->isManager($actor) || $actor->supervisedEmployees()->exists(),
            'can_set_approvers' => $this->isManager($actor) && $target->hasRole('employee') && ! $target->hasAnyRole(['admin', 'superadmin']),
            'approver_ids' => $target->absenceApprovers()->pluck('users.id'),
            'approver_options' => $this->isManager($actor) ? User::query()->assignable()->where('is_active', true)->whereKeyNot($target->id)->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'))->orderBy('name')->get(['id', 'name']) : [],
            'summary' => $this->teamSummary($actor, $from, $until),
            'types' => EmployeeAbsence::TYPES, 'sessions' => $sessions, 'absences' => $absences, 'pending' => $pending,
            'open_session' => AttendanceSession::query()->where('user_id', $actor->id)->whereNull('ended_at')->first(),
            'worked_minutes' => (int) floor($seconds / 60),
        ];
    }

    public function clock(User $actor, bool $enter, ?string $note): AttendanceSession
    {
        $this->authorizeTarget($actor, $actor->id);

        return DB::transaction(function () use ($actor, $enter, $note): AttendanceSession {
            User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $open = AttendanceSession::query()->where('user_id', $actor->id)->whereNull('ended_at')->lockForUpdate()->first();
            if ($enter && $open) {
                throw ValidationException::withMessages(['attendance' => 'Hai già una giornata aperta. Registra prima l’uscita.']);
            }
            if (! $enter && ! $open) {
                throw ValidationException::withMessages(['attendance' => 'Non risulta un ingresso aperto. Aggiorna la pagina.']);
            }
            if ($enter) {
                $now = CarbonImmutable::now('UTC');
                $this->ensureNoAbsence($actor->id, $now, $now->addSecond(), statuses: ['reported', 'approved']);

                return AttendanceSession::create(['user_id' => $actor->id, 'started_at' => $now, 'note' => $note]);
            }
            $open->update(['ended_at' => now('UTC')]);

            return $open;
        });
    }

    private function ensureNoAbsence(int $userId, CarbonImmutable $from, CarbonImmutable $until, ?int $except = null, array $statuses = ['pending', 'reported', 'approved']): void
    {
        $exists = EmployeeAbsence::query()->where('user_id', $userId)->whereIn('status', $statuses)->when($except, fn (Builder $q) => $q->whereKeyNot($except))->where('starts_at', '<', $until)->where('ends_at', '>', $from)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['start_date' => 'Il periodo si sovrappone a un’assenza già comunicata o richiesta.']);
        }
    }

    public function requestAbsence(User $actor, array $data): EmployeeAbsence
    {
        $this->authorizeTarget($actor, $actor->id);
        $allDay = (bool) $data['all_day'];
        $from = CarbonImmutable::parse($data['start_date'].' '.($allDay ? '00:00' : $data['time_from']), self::TIMEZONE)->utc();
        $until = $allDay ? CarbonImmutable::parse($data['end_date'], self::TIMEZONE)->addDay()->utc() : CarbonImmutable::parse($data['end_date'].' '.$data['time_to'], self::TIMEZONE)->utc();
        if ($until <= $from || $from->diffInDays($until) > 366) {
            throw ValidationException::withMessages(['end_date' => 'Indica un intervallo valido, con fine successiva all’inizio e durata massima di un anno.']);
        }

        return DB::transaction(function () use ($actor, $data, $allDay, $from, $until): EmployeeAbsence {
            User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $this->ensureNoAbsence($actor->id, $from, $until);
            if (AttendanceSession::query()->where('user_id', $actor->id)->where('started_at', '<', $until)->where(fn (Builder $q) => $q->where(fn (Builder $open) => $open->whereNull('ended_at')->whereRaw('? > ?', [now('UTC'), $from]))->orWhere('ended_at', '>', $from))->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Sono presenti timbrature nel periodo. Seleziona le sole ore di assenza o chiedi una rettifica.']);
            }

            $absence = EmployeeAbsence::create(['user_id' => $actor->id, 'type' => $data['type'], 'starts_at' => $from, 'ends_at' => $until, 'all_day' => $allDay, 'note' => $data['note'] ?? null, 'certificate_number' => $data['certificate_number'] ?? null, 'status' => $data['type'] === 'malattia' ? 'reported' : 'pending']);
            $this->notifyRequest($actor, $absence);

            return $absence;
        });
    }

    public function updateAbsence(User $actor, EmployeeAbsence $absence, array $data): EmployeeAbsence
    {
        abort_unless($absence->user_id === $actor->id, 403);

        return DB::transaction(function () use ($absence, $data): EmployeeAbsence {
            $absence = EmployeeAbsence::query()->lockForUpdate()->findOrFail($absence->id);
            if (in_array($absence->status, ['cancelled', 'rejected'])) {
                throw ValidationException::withMessages(['absence' => 'Questa richiesta è già chiusa.']);
            }
            if ($absence->type !== 'malattia' && ! empty($data['certificate_number'])) {
                throw ValidationException::withMessages(['certificate_number' => 'Il protocollo è previsto solo per la malattia.']);
            }
            $absence->update($data);

            return $absence;
        });
    }

    public function cancel(User $actor, EmployeeAbsence $absence): void
    {
        abort_unless($absence->user_id === $actor->id, 403);
        DB::transaction(function () use ($absence): void {
            $absence = EmployeeAbsence::query()->lockForUpdate()->findOrFail($absence->id);
            if (! in_array($absence->status, ['pending', 'reported', 'approved'])) {
                throw ValidationException::withMessages(['absence' => 'Questa richiesta è già chiusa.']);
            }
            $absence->update(['status' => 'cancelled', 'cancelled_at' => now('UTC')]);
        });
    }

    public function review(User $actor, EmployeeAbsence $absence, array $data): EmployeeAbsence
    {
        $this->authorizeTarget($actor, $absence->user_id);
        abort_unless($this->canReview($actor, $absence->user_id), 403);

        return DB::transaction(function () use ($actor, $absence, $data): EmployeeAbsence {
            User::query()->whereIn('id', array_unique(array_merge([$absence->user_id], array_filter([$data['replacement_user_id'] ?? null]), array_column($data['assignments'] ?? [], 'user_id'))))->orderBy('id')->lockForUpdate()->get();
            $absence = EmployeeAbsence::query()->lockForUpdate()->findOrFail($absence->id);
            if (! in_array($absence->status, ['pending', 'reported'])) {
                throw ValidationException::withMessages(['absence' => 'La richiesta è già stata gestita. Aggiorna la pagina.']);
            }
            if ($data['status'] === 'approved' && AttendanceSession::query()->where('user_id', $absence->user_id)->where('started_at', '<', $absence->ends_at)->where(fn (Builder $q) => $q->where('ended_at', '>', $absence->starts_at)->orWhere(fn (Builder $open) => $open->whereNull('ended_at')->whereRaw('? > ?', [now('UTC'), $absence->starts_at])))->exists()) {
                throw ValidationException::withMessages(['absence' => 'Sono presenti timbrature nel periodo. Chiedi al dipendente di correggere la richiesta prima di confermarla.']);
            }

            if ($data['status'] === 'approved') {
                $this->reassignAppointments($actor, $absence, $data);
            }
            $absence->update(collect($data)->only(['status', 'review_note'])->all() + ['reviewed_by' => $actor->id, 'reviewed_at' => now('UTC')]);
            $this->notifyPeople(collect([$absence->user]), 'employees.reviewed', 'Richiesta di assenza aggiornata', 'La tua richiesta è stata '.($absence->status === 'approved' ? 'confermata.' : 'rifiutata. Apri il dettaglio per la motivazione.'), $absence);

            return $absence;
        });
    }

    public function correct(User $actor, ?AttendanceSession $session, array $data): AttendanceSession
    {
        $userId = $session?->user_id ?? (int) $data['user_id'];
        $this->authorizeTarget($actor, $userId, true);
        $from = CarbonImmutable::parse($data['started_at'], self::TIMEZONE)->utc();
        $until = ! empty($data['ended_at']) ? CarbonImmutable::parse($data['ended_at'], self::TIMEZONE)->utc() : null;
        if ($from->setTimezone(self::TIMEZONE)->format('Y-m-d\TH:i') !== $data['started_at'] || ($until && $until->setTimezone(self::TIMEZONE)->format('Y-m-d\TH:i') !== $data['ended_at'])) {
            throw ValidationException::withMessages(['started_at' => 'Orario non valido nel fuso di Roma (cambio ora legale).']);
        }
        if ($from->isFuture() || $until?->isFuture() || ($until && $until <= $from)) {
            throw ValidationException::withMessages(['started_at' => 'Le timbrature devono essere nel passato e l’uscita successiva all’ingresso.']);
        }

        return DB::transaction(function () use ($actor, $session, $data, $from, $until, $userId): AttendanceSession {
            User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $session = $session ? AttendanceSession::query()->lockForUpdate()->findOrFail($session->id) : new AttendanceSession(['user_id' => $userId]);
            $overlap = AttendanceSession::query()->where('user_id', $session->user_id)->when($session->exists, fn (Builder $q) => $q->whereKeyNot($session->id))->when($until, fn (Builder $q) => $q->where('started_at', '<', $until))->where(fn (Builder $q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $from))->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['started_at' => 'La rettifica si sovrappone a un’altra timbratura.']);
            }
            $this->ensureNoAbsence($session->user_id, $from, $until ?? CarbonImmutable::now('UTC'));
            $history = $session->corrections ?? [];
            $history[] = ['actor_id' => $actor->id, 'actor_name' => $actor->name, 'at' => now('UTC')->toISOString(), 'reason' => $data['reason'], 'before' => ['started_at' => $session->started_at?->toISOString(), 'ended_at' => $session->ended_at?->toISOString()], 'after' => ['started_at' => $from->toISOString(), 'ended_at' => $until?->toISOString()]];
            $session->fill(['started_at' => $from, 'ended_at' => $until, 'corrections' => $history])->save();

            return $session;
        });
    }

    private function affectedAppointments(EmployeeAbsence $absence): Builder
    {
        return \App\Models\Appointment::query()->where('assigned_user_id', $absence->user_id)->whereIn('status', ['da_confermare', 'confermato'])->where('scheduled_at', '<', $absence->ends_at)->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$absence->starts_at->toDateTimeString()]);
    }

    public function reviewContext(User $actor, EmployeeAbsence $absence): array
    {
        $this->authorizeTarget($actor, $absence->user_id);
        abort_unless($this->canReview($actor, $absence->user_id), 403);
        $appointments = $this->affectedAppointments($absence)->with(['client:id,first_name,last_name', 'practiceType:id,name'])->orderBy('scheduled_at')->get();
        $workers = User::query()->assignable()->with(['roles', 'practiceTypes', 'branches'])->where('is_active', true)->whereKeyNot($absence->user_id)->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'))->orderBy('name')->get();
        $windowStart = $appointments->min('scheduled_at') ?? $absence->starts_at;
        $windowEnd = $appointments->map(fn ($appointment) => $appointment->scheduled_at->copy()->addMinutes($appointment->duration_minutes))->max() ?? $absence->ends_at;
        $busy = \App\Models\Appointment::query()->whereIn('assigned_user_id', $workers->pluck('id'))->whereIn('status', ['da_confermare', 'confermato'])->where('scheduled_at', '<', $windowEnd)->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$windowStart->toDateTimeString()])->get();
        $leaves = EmployeeAbsence::query()->whereIn('user_id', $workers->pluck('id'))->whereIn('status', ['approved', 'reported'])->where('starts_at', '<', $windowEnd)->where('ends_at', '>', $windowStart)->get();

        return ['appointments' => $appointments->map(function ($a) use ($workers, $busy, $leaves): array {
            $end = $a->scheduled_at->copy()->addMinutes($a->duration_minutes);
            $eligible = $workers->filter(fn (User $u) => $u->canAccessBranchId($a->branch_id) && ($u->hasAnyRole(['admin', 'superadmin']) || $u->practiceTypes->contains('id', $a->practice_type_id)) && ! $busy->contains(fn ($b) => $b->assigned_user_id === $u->id && $b->scheduled_at < $end && $b->scheduled_at->copy()->addMinutes($b->duration_minutes) > $a->scheduled_at) && ! $leaves->contains(fn ($l) => $l->user_id === $u->id && $l->starts_at < $end && $l->ends_at > $a->scheduled_at))->pluck('id')->values();

            return ['id' => $a->id, 'scheduled_at' => $a->scheduled_at->toISOString(), 'duration_minutes' => $a->duration_minutes, 'client_name' => trim($a->client->first_name.' '.$a->client->last_name), 'type' => $a->practiceType?->name, 'branch_id' => $a->branch_id, 'eligible_user_ids' => $eligible];
        }), 'replacements' => $workers->map(fn (User $u) => $u->only('id', 'name'))];
    }

    private function reassignAppointments(User $actor, EmployeeAbsence $absence, array $data): void
    {
        $appointments = $this->affectedAppointments($absence)->orderBy('id')->lockForUpdate()->get();
        $mapping = collect($data['assignments'] ?? [])->pluck('user_id', 'appointment_id');
        if ($mapping->keys()->diff($appointments->pluck('id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['assignments' => 'L’elenco degli appuntamenti è cambiato. Riapri la richiesta.']);
        }
        foreach ($appointments as $appointment) {
            $id = $mapping->get($appointment->id) ?? $data['replacement_user_id'] ?? null;
            if (! $id) {
                throw ValidationException::withMessages(['assignments' => 'Scegli un sostituto per tutti gli appuntamenti coinvolti prima di approvare.']);
            }
            $worker = User::query()->assignable()->where('is_active', true)->whereKeyNot($absence->user_id)->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'))->find($id);
            if (! $worker || ! $worker->canAccessBranchId($appointment->branch_id) || (! $worker->hasAnyRole(['admin', 'superadmin']) && ! $worker->practiceTypes()->whereKey($appointment->practice_type_id)->exists())) {
                throw ValidationException::withMessages(['assignments' => 'Il sostituto deve essere attivo, abilitato alla prestazione e alla filiale.']);
            }
            app(\App\Actions\Appointment\UpdateAppointmentAction::class)->execute(['assigned_user_id' => $worker->id], $appointment, $actor->id);
        }
    }

    public function setApprovers(User $actor, User $employee, array $ids): void
    {
        abort_unless($this->isManager($actor) && $employee->hasRole('employee') && ! $employee->hasAnyRole(['admin', 'superadmin', 'cliente']), 403);
        $valid = User::query()->assignable()->whereKey($ids)->whereKeyNot($employee->id)->where('is_active', true)->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'))->count();
        if ($valid !== count($ids)) {
            throw ValidationException::withMessages(['approver_ids' => 'Scegli referenti attivi diversi dal dipendente. I clienti sono esclusi.']);
        }
        DB::transaction(fn () => $employee->absenceApprovers()->sync($ids));
    }

    private function notifyRequest(User $actor, EmployeeAbsence $absence): void
    {
        $people = User::query()->where('is_active', true)->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'cliente'))->where(fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->whereIn('name', ['admin', 'superadmin']))->orWhereIn('id', $actor->absenceApprovers()->select('users.id')))->get();
        $this->notifyPeople($people, 'employees.requested', 'Nuova richiesta di assenza', $actor->name.' ha inviato una richiesta. Apri il dettaglio per gestirla.', $absence);
    }

    private function notifyPeople(\Illuminate\Support\Collection $people, string $event, string $title, string $body, EmployeeAbsence $absence): void
    {
        foreach ($people->unique('id') as $person) {
            $person->notify(new \App\Notifications\DomainNotification($event, ['event_key' => $event, 'section' => 'employees', 'severity' => 'info', 'title' => $title, 'body' => $body, 'action_url' => route('employees.index', ['month' => $absence->starts_at->setTimezone(self::TIMEZONE)->format('Y-m'), 'user_id' => $absence->user_id], false), 'target' => ['resource' => 'employee_attendance', 'id' => $absence->user_id]], ['database']));
        }
    }

    private function teamSummary(User $actor, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $people = $this->staff($actor)->orderBy('name')->get(['users.id', 'name', 'is_active']);
        $absences = EmployeeAbsence::query()->whereIn('user_id', $people->pluck('id'))->where('starts_at', '<', $until)->where('ends_at', '>', $from)->get()->groupBy('user_id');
        $sessions = $this->isManager($actor) ? AttendanceSession::query()->whereIn('user_id', $people->pluck('id'))->whereNotNull('ended_at')->where('started_at', '<', $until)->where('ended_at', '>', $from)->get()->groupBy('user_id') : collect();

        return $people->map(function (User $person) use ($absences, $sessions, $from, $until): array {
            $byType = [];
            $days = 0;
            $hours = 0;
            $pending = 0;
            foreach ($absences->get($person->id, collect()) as $a) {
                if ($a->status === 'pending') {
                    $pending++;
                }
                if (! in_array($a->status, ['approved', 'reported'])) {
                    continue;
                }
                $start = $a->starts_at->max($from);
                $end = $a->ends_at->min($until);
                $countDays = $a->all_day ? (int) $start->setTimezone(self::TIMEZONE)->startOfDay()->diffInDays($end->setTimezone(self::TIMEZONE)->startOfDay()) : 0;
                $countHours = $a->all_day ? 0 : round($start->diffInMinutes($end) / 60, 2);
                $byType[$a->type] ??= ['days' => 0, 'hours' => 0, 'count' => 0];
                $byType[$a->type]['days'] += $countDays;
                $byType[$a->type]['hours'] += $countHours;
                $byType[$a->type]['count']++;
                $days += $countDays;
                $hours += $countHours;
            }
            $minutes = (int) $sessions->get($person->id, collect())->sum(fn ($s) => $s->started_at->max($from)->diffInMinutes($s->ended_at->min($until)));

            return ['id' => $person->id, 'name' => $person->name, 'absence_days' => $days, 'absence_hours' => round($hours, 2), 'pending_count' => $pending, 'worked_minutes' => $minutes, 'by_type' => $byType];
        })->all();
    }

    public function calendar(User $actor, string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from, self::TIMEZONE)->startOfDay()->utc();
        $end = CarbonImmutable::parse($to, self::TIMEZONE)->addDay()->startOfDay()->utc();

        return EmployeeAbsence::query()->with('user:id,name')->whereIn('user_id', $this->staff($actor)->select('users.id'))->whereIn('status', ['approved', 'reported', 'pending'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->orderBy('starts_at')->get()->map(fn ($a) => [
            'id' => 'absence-'.$a->id, 'title' => $a->user->name.' · '.EmployeeAbsence::TYPES[$a->type].($a->status === 'pending' ? ' (in attesa)' : ''), 'start' => $a->all_day ? $a->starts_at->setTimezone(self::TIMEZONE)->toDateString() : $a->starts_at->setTimezone(self::TIMEZONE)->toIso8601String(), 'end' => $a->all_day ? $a->ends_at->setTimezone(self::TIMEZONE)->toDateString() : $a->ends_at->setTimezone(self::TIMEZONE)->toIso8601String(), 'allDay' => $a->all_day, 'editable' => false, 'backgroundColor' => '#B45309', 'borderColor' => '#B45309', 'extendedProps' => ['kind' => 'absence', 'status' => $a->status, 'assigned_user_id' => $a->user_id, 'user_name' => $a->user->name],
        ])->all();
    }

    public function export(User $actor, array $filters): array
    {
        $target = (int) ($filters['user_id'] ?? $actor->id);
        abort_unless($target === $actor->id || $this->isManager($actor), 403);
        $data = $this->overview($actor, $filters);
        $day = CarbonImmutable::createFromFormat('!Y-m', $data['month'], self::TIMEZONE);
        $until = $day->addMonth();
        $rows = [['Dipendente', $data['target']['name']], ['Mese', $data['month']], ['Fuso orario', self::TIMEZONE], ['Data', 'Ingressi', 'Uscite', 'Ore lavorate', 'Assenze e permessi']];
        for (; $day < $until; $day = $day->addDay()) {
            $start = $day->utc();
            $end = $day->addDay()->utc();
            $ins = [];
            $outs = [];
            $minutes = 0;
            $labels = [];
            foreach ($data['sessions'] as $session) {
                if ($session->started_at >= $end || ($session->ended_at && $session->ended_at <= $start)) {
                    continue;
                }
                $ins[] = $session->started_at->max($start)->setTimezone(self::TIMEZONE)->format('H:i');
                $outs[] = $session->ended_at ? $session->ended_at->min($end)->setTimezone(self::TIMEZONE)->format('H:i') : 'In corso';
                if ($session->ended_at) {
                    $minutes += $session->started_at->max($start)->diffInMinutes($session->ended_at->min($end));
                }
            }
            foreach ($data['absences'] as $a) {
                if ($a->starts_at < $end && $a->ends_at > $start && ! in_array($a->status, ['cancelled', 'rejected'])) {
                    $labels[] = EmployeeAbsence::TYPES[$a->type].' ('.($a->status === 'pending' ? 'in attesa' : 'confermata').')';
                }
            }
            $rows[] = [$day->format('d/m/Y'), implode(' / ', $ins), implode(' / ', $outs), number_format($minutes / 60, 2, ',', ''), implode(' / ', $labels)];
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($v) => preg_match('/^(?:\s*[=+@-]|[\t\r\n])/u', (string) $v) ? "'".$v : (string) $v, $row), ';', '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return ['filename' => 'presenze-'.$target.'-'.$data['month'].'.csv', 'content' => $content];
    }
}
