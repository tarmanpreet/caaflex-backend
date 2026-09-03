<?php

namespace App\Actions\Procedure;

use App\Models\Procedure;

class SyncProcedureDeadlineTemplatesAction
{
    /** @param array<int, array<string, mixed>> $templates */
    public function execute(Procedure $procedure, array $templates): void
    {
        $keptIds = [];

        foreach (array_values($templates) as $position => $templateData) {
            $templateId = $templateData['id'] ?? null;
            unset($templateData['id']);
            $templateData['position'] = $position;

            if ($templateId) {
                $template = $procedure->deadlineTemplates()->findOrFail($templateId);
                $template->update($templateData);
                $keptIds[] = $template->id;

                continue;
            }

            $keptIds[] = $procedure->deadlineTemplates()->create($templateData)->id;
        }

        $query = $procedure->deadlineTemplates();

        if ($keptIds !== []) {
            $query->whereNotIn('id', $keptIds);
        }

        $query->delete();
    }
}
