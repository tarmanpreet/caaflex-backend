<?php

namespace App\Actions\PracticeDeadline;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use App\Services\NotificationManager;
use Illuminate\Support\Facades\DB;

class UpdatePracticeDeadlineAction
{
    public function __construct(private NotificationManager $notifications) {}

    public function execute(array $data, Practice $practice, PracticeDeadline $deadline, int $actorId): PracticeDeadline
    {
        $oldAssigneeId = $deadline->user_id;
        $oldStatus = $deadline->status;
        DB::transaction(function () use ($data, $deadline): void {
            $deadline->update($data);

            if ($deadline->kind !== PracticeDeadline::KIND_PROCEDURE_PRIMARY) {
                return;
            }

            $openSteps = $deadline->steps()
                ->whereNotIn('status', [PracticeDeadline::STATUS_COMPLETED, PracticeDeadline::STATUS_CANCELLED])
                ->get();

            foreach ($openSteps as $step) {
                $step->update([
                    'deadline_at' => $deadline->deadline_at->copy()->subMinutes($step->advance_minutes ?? 0),
                    'user_id' => $deadline->user_id,
                ]);
            }
        });
        $changedFields = array_keys($deadline->getChanges());
        $deadline->refresh()->load('assignee');
        $actionUrl = route('practices.show', $practice, false).'#steps';

        if ($oldAssigneeId !== $deadline->user_id) {
            $recipients = User::query()->whereKey(array_filter([$oldAssigneeId, $deadline->user_id]))->get();
            $this->notifications->send(
                $recipients,
                'deadlines.assigned',
                'deadlines',
                'Assegnazione step modificata',
                "È cambiato l’assegnatario dello step «{$deadline->title}».",
                $deadline,
                $actionUrl,
                $actorId,
            );
        }

        if ($oldStatus !== $deadline->status && $deadline->assignee) {
            $this->notifications->send(
                [$deadline->assignee],
                'deadlines.status_changed',
                'deadlines',
                'Stato step aggiornato',
                "Lo step «{$deadline->title}» è passato da {$oldStatus} a {$deadline->status}.",
                $deadline,
                $actionUrl,
                $actorId,
            );
        }

        $isContentChanged = array_intersect($changedFields, ['title', 'notes', 'priority', 'deadline_at']) !== [];

        if ($isContentChanged && $deadline->assignee) {
            $this->notifications->send(
                [$deadline->assignee],
                'deadlines.changed',
                'deadlines',
                'Step modificato',
                "Sono stati aggiornati i dettagli dello step «{$deadline->title}».",
                $deadline,
                $actionUrl,
                $actorId,
            );
        }

        return $deadline;
    }
}
