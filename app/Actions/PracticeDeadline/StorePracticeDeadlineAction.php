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
        $generateProcedureSteps = (bool) ($data['generate_procedure_steps'] ?? false);
        unset($data['generate_procedure_steps']);

        $deadline = DB::transaction(function () use ($createdBy, $data, $generateProcedureSteps, $practice): PracticeDeadline {
            if ($generateProcedureSteps) {
                $practice->loadMissing('procedure.deadlineTemplates');
            }

            $templates = $practice->procedure?->deadlineTemplates ?? collect();
            $shouldGenerateSteps = $generateProcedureSteps && $templates->isNotEmpty();

            $deadline = PracticeDeadline::query()->create([
                ...$data,
                'practice_id' => $practice->id,
                'kind' => $shouldGenerateSteps
                    ? PracticeDeadline::KIND_PROCEDURE_PRIMARY
                    : PracticeDeadline::KIND_MANUAL,
                'created_by' => $createdBy,
                'status' => $data['status'] ?? PracticeDeadline::STATUS_PENDING,
            ]);

            if (! $shouldGenerateSteps) {
                return $deadline;
            }

            foreach ($templates as $template) {
                $advanceMinutes = ($template->offset_days * 1440) + ($template->offset_hours * 60);

                $deadline->steps()->create([
                    'practice_id' => $practice->id,
                    'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
                    'procedure_deadline_template_id' => $template->id,
                    'advance_minutes' => $advanceMinutes,
                    'user_id' => $deadline->user_id,
                    'title' => $template->title,
                    'notes' => $template->notes,
                    'deadline_at' => $deadline->deadline_at->copy()->subMinutes($advanceMinutes),
                    'status' => PracticeDeadline::STATUS_PENDING,
                    'priority' => $template->priority,
                    'created_by' => $createdBy,
                ]);
            }

            return $deadline;
        });

        if ($deadline->user_id) {
            $deadline->load('assignee');
            $this->notifications->send(
                [$deadline->assignee],
                'deadlines.assigned',
                'deadlines',
                'Nuovo step assegnato',
                "Ti è stato assegnato lo step «{$deadline->title}».",
                $deadline,
                route('practices.show', $practice, false).'#steps',
                $createdBy,
            );
        }

        return $deadline->refresh()->load('steps');
    }
}
