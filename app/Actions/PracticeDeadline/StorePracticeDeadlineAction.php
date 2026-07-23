<?php

namespace App\Actions\PracticeDeadline;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Services\NotificationManager;
use Illuminate\Support\Facades\DB;

class StorePracticeDeadlineAction
{
    public function __construct(private NotificationManager $notifications) {}

    public function execute(array $data, Practice $practice, int $createdBy): PracticeDeadline
    {
        $deadline = DB::transaction(fn (): PracticeDeadline => PracticeDeadline::query()->create([
            ...$data,
            'practice_id' => $practice->id,
            'created_by' => $createdBy,
            'status' => $data['status'] ?? PracticeDeadline::STATUS_PENDING,
        ]));

        if ($deadline->user_id) {
            $deadline->load('assignee');
            $this->notifications->send(
                [$deadline->assignee],
                'deadlines.assigned',
                'deadlines',
                'Nuova scadenza assegnata',
                "Ti è stata assegnata la scadenza «{$deadline->title}».",
                $deadline,
                route('practices.show', $practice, false).'#deadlines',
                $createdBy,
            );
        }

        return $deadline->refresh();
    }
}
