<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PracticeDeadline;
use App\Models\ScheduledNotificationOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderScheduler
{
    public function __construct(
        private NotificationPreferenceService $preferences,
        private NotificationManager $notifications,
    ) {}

    public function synchronize(): void
    {
        User::query()
            ->where('is_active', true)
            ->with('clientProfile:id,user_id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $this->synchronizeAppointments($user);
                    $this->synchronizeDeadlines($user);
                }
            });
    }

    public function dispatchDue(): int
    {
        $now = now();

        ScheduledNotificationOccurrence::query()
            ->where('status', ScheduledNotificationOccurrence::STATUS_PENDING)
            ->where('expires_at', '<', $now)
            ->update([
                'status' => ScheduledNotificationOccurrence::STATUS_CANCELLED,
                'cancelled_at' => $now,
            ]);

        $count = 0;

        ScheduledNotificationOccurrence::query()
            ->where('status', ScheduledNotificationOccurrence::STATUS_PENDING)
            ->where('scheduled_for', '<=', $now)
            ->where('expires_at', '>=', $now)
            ->with('user')
            ->orderBy('id')
            ->chunkById(100, function ($occurrences) use (&$count): void {
                foreach ($occurrences as $occurrence) {
                    if (! $this->isStillValid($occurrence)) {
                        $occurrence->update([
                            'status' => ScheduledNotificationOccurrence::STATUS_CANCELLED,
                            'cancelled_at' => now(),
                        ]);

                        continue;
                    }

                    try {
                        $this->sendOccurrence($occurrence);
                        $occurrence->update([
                            'status' => ScheduledNotificationOccurrence::STATUS_DISPATCHED,
                            'dispatched_at' => now(),
                        ]);
                        $count++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        Log::warning('Invio reminder pianificato non riuscito.', [
                            'occurrence_id' => $occurrence->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    private function synchronizeAppointments(User $user): void
    {
        $offsets = $this->preferences->reminderOffsets($user, 'appointments');

        if ($offsets === [] || $this->preferences->channels($user, 'appointments') === []) {
            return;
        }

        $maximumOffset = max($offsets);

        Appointment::query()
            ->whereNotIn('status', ['completato', 'cancellato'])
            ->whereBetween('scheduled_at', [now()->subMinutes(15), now()->addMinutes($maximumOffset + 15)])
            ->where(function ($query) use ($user): void {
                $query->where('assigned_user_id', $user->id);

                if ($user->clientProfile) {
                    $query->orWhere('client_profile_id', $user->clientProfile->id);
                }
            })
            ->each(fn (Appointment $appointment) => $this->createOccurrences($user, $appointment, 'appointments.reminder', $appointment->scheduled_at, $offsets));
    }

    private function synchronizeDeadlines(User $user): void
    {
        $offsets = $this->preferences->reminderOffsets($user, 'deadlines');

        if ($offsets === [] || $this->preferences->channels($user, 'deadlines') === []) {
            return;
        }

        $maximumOffset = max($offsets);

        PracticeDeadline::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [PracticeDeadline::STATUS_COMPLETED, PracticeDeadline::STATUS_CANCELLED])
            ->whereBetween('deadline_at', [now()->subMinutes(15), now()->addMinutes($maximumOffset + 15)])
            ->each(fn (PracticeDeadline $deadline) => $this->createOccurrences($user, $deadline, 'deadlines.reminder', $deadline->deadline_at, $offsets));
    }

    /** @param array<int, int> $offsets */
    private function createOccurrences(User $user, Model $subject, string $eventKey, Carbon $subjectAt, array $offsets): void
    {
        foreach ($offsets as $minutesBefore) {
            $scheduledFor = $subjectAt->copy()->subMinutes($minutesBefore);

            if ($scheduledFor->lt(now()->subMinutes(15))) {
                continue;
            }

            $key = hash('sha256', implode('|', [
                $user->id,
                $eventKey,
                $subject->getMorphClass(),
                $subject->getKey(),
                $subjectAt->getTimestamp(),
                $minutesBefore,
            ]));

            ScheduledNotificationOccurrence::query()->firstOrCreate(
                ['deduplication_key' => $key],
                [
                    'user_id' => $user->id,
                    'event_key' => $eventKey,
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'minutes_before' => $minutesBefore,
                    'subject_scheduled_at' => $subjectAt,
                    'scheduled_for' => $scheduledFor,
                    'expires_at' => $scheduledFor->copy()->addMinutes(15),
                ]
            );
        }
    }

    private function isStillValid(ScheduledNotificationOccurrence $occurrence): bool
    {
        $subject = $occurrence->subject()->first();
        $user = $occurrence->user;

        if (! $subject || ! $user?->is_active) {
            return false;
        }

        if ($subject instanceof Appointment) {
            $isRecipient = $subject->assigned_user_id === $user->id
                || $subject->client()->where('user_id', $user->id)->exists();

            return $isRecipient
                && ! in_array($subject->status, ['completato', 'cancellato'], true)
                && $subject->scheduled_at->equalTo($occurrence->subject_scheduled_at);
        }

        if ($subject instanceof PracticeDeadline) {
            return $subject->user_id === $user->id
                && ! in_array($subject->status, [PracticeDeadline::STATUS_COMPLETED, PracticeDeadline::STATUS_CANCELLED], true)
                && $subject->deadline_at->equalTo($occurrence->subject_scheduled_at);
        }

        return false;
    }

    private function sendOccurrence(ScheduledNotificationOccurrence $occurrence): void
    {
        $subject = $occurrence->subject()->firstOrFail();
        $when = $occurrence->subject_scheduled_at
            ->timezone(config('notifications.business_timezone'))
            ->format('d/m/Y H:i');

        if ($subject instanceof Appointment) {
            $subject->loadMissing('client');
            $clientName = trim($subject->client->first_name.' '.$subject->client->last_name);
            $this->notifications->send(
                [$occurrence->user],
                'appointments.reminder',
                'appointments',
                'Promemoria appuntamento',
                "Appuntamento con {$clientName} previsto il {$when}.",
                $subject,
                route('appointments.show', $subject, false),
                severity: 'warning',
            );

            return;
        }

        if ($subject instanceof PracticeDeadline) {
            $this->notifications->send(
                [$occurrence->user],
                'deadlines.reminder',
                'deadlines',
                'Promemoria scadenza',
                "{$subject->title} scade il {$when}.",
                $subject,
                route('practices.show', $subject->practice_id, false).'#deadlines',
                severity: 'warning',
            );
        }
    }
}
