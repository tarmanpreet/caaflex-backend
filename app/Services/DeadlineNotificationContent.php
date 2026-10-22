<?php

namespace App\Services;

use App\Models\PracticeDeadline;

class DeadlineNotificationContent
{
    public function context(PracticeDeadline $deadline): string
    {
        $deadline->loadMissing(['practice.client', 'practice.practiceType', 'assignee']);
        $practice = $deadline->practice;

        if (! $practice) {
            return 'Pratica non più disponibile.';
        }

        $clientName = trim(($practice->client?->first_name ?? '').' '.($practice->client?->last_name ?? ''));
        $type = $practice->practiceType?->name ?? $practice->type;

        return implode(' · ', array_filter([
            "Pratica #{$practice->id} ({$practice->tracking_code})",
            trim($type.' '.$practice->reference_year),
            $clientName !== '' ? "Cliente: {$clientName}" : null,
            $deadline->assignee ? "Assegnato a: {$deadline->assignee->name}" : null,
        ])).'.';
    }

    /** @return array{title: string, body: string} */
    public function reminder(PracticeDeadline $deadline): array
    {
        $when = $deadline->deadline_at->copy()
            ->timezone(config('notifications.business_timezone'))
            ->format('d/m/Y H:i');

        return [
            'title' => "Promemoria step: {$deadline->title}",
            'body' => "Completa «{$deadline->title}» entro il {$when} (ora di Roma). ".$this->context($deadline),
        ];
    }
}
