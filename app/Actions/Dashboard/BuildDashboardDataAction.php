<?php

namespace App\Actions\Dashboard;

use App\Models\Appointment;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeDocument;
use App\Models\PracticeNote;
use App\Models\PracticeStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildDashboardDataAction
{
    public function execute(User $user): array
    {
        $practiceIds = $this->scopedPracticeQuery($user)->pluck('practices.id');

        return [
            'stats' => $this->buildStats($user, $practiceIds),
            'deadlines' => $this->buildDeadlines($practiceIds),
            'activities' => $this->buildActivities($practiceIds),
            'practices' => $this->buildPractices($practiceIds),
            'efficiency' => $this->buildEfficiency($practiceIds),
        ];
    }

    private function buildStats(User $user, Collection $practiceIds): array
    {
        $activePracticeCount = Practice::query()
            ->whereIn('id', $practiceIds)
            ->whereIn('status', ['in_lavorazione', 'in_attesa_documenti'])
            ->count();

        $openDeadlineCount = PracticeDeadline::query()
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('status', [
                PracticeDeadline::STATUS_PENDING,
                PracticeDeadline::STATUS_IN_PROGRESS,
            ])
            ->count();

        $confirmAppointmentCount = $this->scopedAppointmentQuery($user)
            ->where('status', 'da_confermare')
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->count();

        return [
            [
                'title' => 'Pratiche attive',
                'value' => $activePracticeCount,
                'caption' => 'In lavorazione o in attesa documenti',
                'tone' => 'primary',
            ],
            [
                'title' => 'Scadenze aperte',
                'value' => $openDeadlineCount,
                'caption' => 'Pending o in corso',
                'tone' => 'tertiary',
            ],
            [
                'title' => 'Appuntamenti da confermare',
                'value' => $confirmAppointmentCount,
                'caption' => 'Da oggi in avanti',
                'tone' => 'neutral',
            ],
        ];
    }

    private function buildDeadlines(Collection $practiceIds): array
    {
        return PracticeDeadline::query()
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('status', [
                PracticeDeadline::STATUS_PENDING,
                PracticeDeadline::STATUS_IN_PROGRESS,
            ])
            ->with([
                'practice:id,client_profile_id,type,status',
                'practice.client:id,first_name,last_name',
                'assignee:id,name',
            ])
            ->orderBy('deadline_at')
            ->limit(6)
            ->get()
            ->map(function (PracticeDeadline $deadline): array {
                $clientName = trim(implode(' ', array_filter([
                    $deadline->practice?->client?->first_name,
                    $deadline->practice?->client?->last_name,
                ])));

                return [
                    'id' => $deadline->id,
                    'title' => $deadline->title,
                    'deadline_at' => optional($deadline->deadline_at)?->toIso8601String(),
                    'status' => $deadline->status,
                    'priority' => $deadline->priority,
                    'practice' => [
                        'id' => $deadline->practice?->id,
                        'type' => $deadline->practice?->type,
                        'client_name' => $clientName !== '' ? $clientName : 'Cliente non disponibile',
                    ],
                    'assignee' => [
                        'name' => $deadline->assignee?->name,
                    ],
                    'notes' => $deadline->notes,
                ];
            })
            ->values()
            ->all();
    }

    private function buildActivities(Collection $practiceIds): array
    {
        $documents = PracticeDocument::query()
            ->whereIn('practice_id', $practiceIds)
            ->with(['practice.client:id,first_name,last_name', 'uploader:id,name'])
            ->latest()
            ->limit(4)
            ->get()
            ->map(function (PracticeDocument $document): array {
                return [
                    'id' => 'document-' . $document->id,
                    'kind' => 'document',
                    'label' => 'Documento caricato',
                    'title' => $document->original_name,
                    'detail' => $this->formatPracticeContext($document->practice?->type, $document->practice?->client?->first_name, $document->practice?->client?->last_name),
                    'meta' => $document->uploader?->name ?: 'Sistema',
                    'occurred_at' => optional($document->created_at)?->toIso8601String(),
                    'tone' => 'primary',
                ];
            });

        $notes = PracticeNote::query()
            ->whereIn('practice_id', $practiceIds)
            ->with(['practice.client:id,first_name,last_name', 'author:id,name'])
            ->latest()
            ->limit(4)
            ->get()
            ->map(function (PracticeNote $note): array {
                return [
                    'id' => 'note-' . $note->id,
                    'kind' => 'note',
                    'label' => 'Nota aggiunta',
                    'title' => str($note->body)->limit(72)->toString(),
                    'detail' => $this->formatPracticeContext($note->practice?->type, $note->practice?->client?->first_name, $note->practice?->client?->last_name),
                    'meta' => $note->author?->name ?: 'Utente non disponibile',
                    'occurred_at' => optional($note->created_at)?->toIso8601String(),
                    'tone' => 'tertiary',
                ];
            });

        $statusLogs = PracticeStatusLog::query()
            ->whereIn('practice_id', $practiceIds)
            ->with(['practice.client:id,first_name,last_name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(function (PracticeStatusLog $log): array {
                $transition = $log->old_status
                    ? sprintf('%s -> %s', $this->formatStatus($log->old_status), $this->formatStatus($log->new_status))
                    : $this->formatStatus($log->new_status);

                return [
                    'id' => 'status-' . $log->id,
                    'kind' => 'status',
                    'label' => 'Cambio stato',
                    'title' => $transition,
                    'detail' => $this->formatPracticeContext($log->practice?->type, $log->practice?->client?->first_name, $log->practice?->client?->last_name),
                    'meta' => $log->user?->name ?: 'Sistema',
                    'occurred_at' => optional($log->created_at)?->toIso8601String(),
                    'tone' => 'neutral',
                ];
            });

        return $documents
            ->concat($notes)
            ->concat($statusLogs)
            ->sortByDesc('occurred_at')
            ->take(6)
            ->values()
            ->all();
    }

    private function buildPractices(Collection $practiceIds): array
    {
        return Practice::query()
            ->whereIn('id', $practiceIds)
            ->whereIn('status', ['in_lavorazione', 'in_attesa_documenti'])
            ->with(['client:id,first_name,last_name,fiscal_code'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Practice $practice): array {
                return [
                    'id' => $practice->id,
                    'client_name' => trim(implode(' ', array_filter([
                        $practice->client?->first_name,
                        $practice->client?->last_name,
                    ]))) ?: 'Cliente non disponibile',
                    'tax_id' => $practice->client?->fiscal_code ?: '—',
                    'type' => $practice->type,
                    'status' => $practice->status,
                    'updated_at' => optional($practice->updated_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildEfficiency(Collection $practiceIds): array
    {
        $windowStart = now()->subDays(7);
        $windowEnd = now();

        $baseQuery = PracticeDeadline::query()
            ->whereIn('practice_id', $practiceIds)
            ->whereBetween('deadline_at', [$windowStart, $windowEnd])
            ->where('status', '!=', PracticeDeadline::STATUS_CANCELLED);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)
            ->where('status', PracticeDeadline::STATUS_COMPLETED)
            ->count();

        $value = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'value' => $value,
            'caption' => 'Scadenze completate negli ultimi 7 giorni',
            'completed' => $completed,
            'total' => $total,
        ];
    }

    private function scopedPracticeQuery(User $user): Builder
    {
        $query = Practice::query();

        if ($user->hasPermissionTo('practices.view-any')) {
            return $query;
        }

        if ($user->hasPermissionTo('practices.view-own')) {
            $query->whereHas('assignedUsers', fn (Builder $builder) => $builder->where('users.id', $user->id));

            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    private function scopedAppointmentQuery(User $user): Builder
    {
        $query = Appointment::query();

        if ($user->hasPermissionTo('appointments.view-any')) {
            return $query;
        }

        if ($user->hasPermissionTo('appointments.view-own')) {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function formatPracticeContext(?string $practiceType, ?string $firstName, ?string $lastName): string
    {
        $clientName = trim(implode(' ', array_filter([$firstName, $lastName])));
        $parts = array_filter([$practiceType, $clientName]);

        return $parts !== [] ? implode(' · ', $parts) : 'Pratica senza contesto disponibile';
    }

    private function formatStatus(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }
}