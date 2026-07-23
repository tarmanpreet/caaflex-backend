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
        DB::transaction(fn () => $deadline->update($data));
        $changedFields = array_keys($deadline->getChanges());
        $deadline->refresh()->load('assignee');
        $actionUrl = route('practices.show', $practice, false).'#deadlines';

        if ($oldAssigneeId !== $deadline->user_id) {
            $recipients = User::query()->whereKey(array_filter([$oldAssigneeId, $deadline->user_id]))->get();
            $this->notifications->send(
                $recipients,
                'deadlines.assigned',
                'deadlines',
                'Assegnazione scadenza modificata',
                "È cambiato l’assegnatario della scadenza «{$deadline->title}».",
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
                'Stato scadenza aggiornato',
                "La scadenza «{$deadline->title}» è passata da {$oldStatus} a {$deadline->status}.",
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
                'Scadenza modificata',
                "Sono stati aggiornati i dettagli della scadenza «{$deadline->title}».",
                $deadline,
                $actionUrl,
                $actorId,
            );
        }

        return $deadline;
    }
}
