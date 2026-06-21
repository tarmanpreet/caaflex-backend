<?php

namespace App\Actions\Dashboard;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildDashboardNoticesAction
{
    public function execute(User $user): array
    {
        $practiceIds = $this->scopedPracticeQuery($user)->pluck('practices.id');

        return PracticeDeadline::query()
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('status', [
                PracticeDeadline::STATUS_PENDING,
                PracticeDeadline::STATUS_IN_PROGRESS,
            ])
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

    private function mapPriority(int|string|null $priority): string
    {
        return match ($priority) {
            PracticeDeadline::PRIORITY_URGENT, PracticeDeadline::PRIORITY_HIGH => 'high',
            PracticeDeadline::PRIORITY_MEDIUM => 'medium',
            default => 'low',
        };
    }
}