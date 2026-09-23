<?php

namespace App\Actions\Practice;

use App\Actions\PracticeDeadline\StorePracticeDeadlineAction;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeStatusLog;
use App\Models\Procedure;
use App\Services\NotificationManager;
use Illuminate\Support\Arr;

class StorePracticeAction
{
    public function __construct(
        private NotificationManager $notifications,
        private StorePracticeDeadlineAction $storeDeadline,
    ) {}

    public function execute(array $data, int $createdBy): Practice
    {
        $data['branch_id'] ??= ClientProfile::query()->findOrFail($data['client_profile_id'])->branch_id;
        $procedure = $this->procedureFor($data);

        if (blank($data['deadline_at'] ?? null) && $procedure?->deadline_days !== null) {
            $data['deadline_at'] = now()->addDays($procedure->deadline_days)->seconds(0);
        }

        $practice = Practice::create(
            Arr::except($data, ['user_ids']) + [
                'status' => $data['status'] ?? 'nuova',
                'created_by' => $createdBy,
            ]
        );

        $practice->assignedUsers()->sync($data['user_ids'] ?? []);

        PracticeStatusLog::create([
            'practice_id' => $practice->id,
            'user_id' => $createdBy,
            'old_status' => null,
            'new_status' => $practice->status,
        ]);

        $practice->load('assignedUsers');
        $primaryAssigneeId = isset($data['user_ids'][0]) ? (int) $data['user_ids'][0] : null;
        $this->createProcedureDeadlines($practice, $procedure, $primaryAssigneeId, $createdBy);

        $this->notifications->send(
            $practice->assignedUsers,
            'practices.assigned',
            'practices',
            'Nuova pratica assegnata',
            "Ti è stata assegnata la pratica #{$practice->id}.",
            $practice,
            route('practices.show', $practice, false),
            $createdBy,
        );

        return $practice;
    }

    /** @param array<string, mixed> $data */
    private function procedureFor(array $data): ?Procedure
    {
        $procedureId = $data['procedure_id'] ?? null;

        if (! $procedureId) {
            return null;
        }

        return Procedure::query()
            ->with('deadlineTemplates')
            ->findOrFail($procedureId);
    }

    private function createProcedureDeadlines(
        Practice $practice,
        ?Procedure $procedure,
        ?int $assigneeId,
        int $createdBy,
    ): void {
        if (! $practice->deadline_at || ! $procedure || $procedure->deadlineTemplates->isEmpty()) {
            return;
        }

        $this->storeDeadline->execute([
            'title' => $procedure->name,
            'deadline_at' => $practice->deadline_at,
            'priority' => PracticeDeadline::PRIORITY_MEDIUM,
            'user_id' => $assigneeId,
            'generate_procedure_steps' => true,
        ], $practice, $createdBy);
    }
}
