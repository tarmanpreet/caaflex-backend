<?php

namespace App\Actions\Dashboard;

use App\Actions\PracticeDeadline\ScopeVisiblePracticeDeadlineAction;
use App\Models\PracticeDeadline;
use App\Models\User;

class BuildDashboardNoticesAction
{
    public function __construct(private ScopeVisiblePracticeDeadlineAction $visibleDeadlines) {}

    public function execute(User $user): array
    {
        $query = $this->visibleDeadlines->execute($user)
            ->whereIn('status', [
                PracticeDeadline::STATUS_PENDING,
                PracticeDeadline::STATUS_IN_PROGRESS,
            ]);

        if (! $user->hasPermissionTo('practices.view-any')) {
            $query->orderByRaw('case when user_id = ? then 0 else 1 end', [$user->id]);
        }

        return $query
            ->with(['practice:id,client_profile_id,type', 'practice.client:id,first_name,last_name'])
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
                    'body' => $clientName !== ''
                        ? sprintf('Pratica %s per %s', $deadline->practice?->type ?? 'senza tipo', $clientName)
                        : 'Pratica in attesa di lavorazione.',
                    'priority' => $this->mapPriority($deadline->priority),
                    'expires_at' => optional($deadline->deadline_at)?->toIso8601String(),
                    'target_route' => '/(operator)/practices',
                ];
            })
            ->values()
            ->all();
    }

    private function mapPriority(int|string|null $priority): string
    {
        return match ($priority) {
            PracticeDeadline::PRIORITY_URGENT, PracticeDeadline::PRIORITY_HIGH => 'high',
            PracticeDeadline::PRIORITY_MEDIUM => 'medium',
            default => 'low',
        };
    }
}
