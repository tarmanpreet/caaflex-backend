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
        if ($actor->hasRole('superadmin')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($actor): void {
            $q->whereKey($actor->id);
            if ($this->isManager($actor)) {
                $ids = $actor->accessibleBranchIds();
                $q->orWhere(function (Builder $q) use ($ids): void {
                    $q->role('employee')->whereDoesntHave('roles', fn (Builder $r) => $r->whereIn('name', ['admin', 'superadmin']))
                        ->where(function (Builder $q) use ($ids): void {
                            $q->whereHas('branches', fn (Builder $b) => $b->whereIn('branches.id', $ids));
                            if (\App\Models\Branch::query()->whereNull('parent_id')->whereNotIn('id', $ids)->doesntExist()) {
                                $q->orWhereDoesntHave('branches');
                            }
                        });
                });
            }
        });
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
        $pending = $this->isManager($actor) ? EmployeeAbsence::query()->with('user:id,name')->whereIn('user_id', $this->staff($actor)->select('users.id'))->where('user_id', '!=', $actor->id)->whereIn('status', ['pending', 'reported'])->orderBy('starts_at')->get() : collect();
        $seconds = $sessions->sum(fn (AttendanceSession $s) => $s->ended_at ? max(0, $s->started_at->max($from)->diffInSeconds($s->ended_at->min($until), false)) : 0);

        return [
            'month' => $month, 'timezone' => self::TIMEZONE, 'target' => $target->only('id', 'name'), 'can_manage' => $this->isManager($actor),
            'staff' => $this->staff($actor)->orderBy('name')->get(['users.id', 'name', 'is_active']),
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

            return EmployeeAbsence::create(['user_id' => $actor->id, 'type' => $data['type'], 'starts_at' => $from, 'ends_at' => $until, 'all_day' => $allDay, 'note' => $data['note'] ?? null, 'certificate_number' => $data['certificate_number'] ?? null, 'status' => $data['type'] === 'malattia' ? 'reported' : 'pending']);
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
        $this->authorizeTarget($actor, $absence->user_id, true);
        abort_if($absence->user_id === $actor->id, 403, 'Le tue richieste devono essere gestite da un altro amministratore.');

        return DB::transaction(function () use ($actor, $absence, $data): EmployeeAbsence {
            User::query()->whereKey($absence->user_id)->lockForUpdate()->firstOrFail();
            $absence = EmployeeAbsence::query()->lockForUpdate()->findOrFail($absence->id);
            if (! in_array($absence->status, ['pending', 'reported'])) {
                throw ValidationException::withMessages(['absence' => 'La richiesta è già stata gestita. Aggiorna la pagina.']);
            }
            if ($data['status'] === 'approved' && AttendanceSession::query()->where('user_id', $absence->user_id)->where('started_at', '<', $absence->ends_at)->where(fn (Builder $q) => $q->where('ended_at', '>', $absence->starts_at)->orWhere(fn (Builder $open) => $open->whereNull('ended_at')->whereRaw('? > ?', [now('UTC'), $absence->starts_at])))->exists()) {
                throw ValidationException::withMessages(['absence' => 'Sono presenti timbrature nel periodo. Chiedi al dipendente di correggere la richiesta prima di confermarla.']);
            }

            $absence->update($data + ['reviewed_by' => $actor->id, 'reviewed_at' => now('UTC')]);

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
}
