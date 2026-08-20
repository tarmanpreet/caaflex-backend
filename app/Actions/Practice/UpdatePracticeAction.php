<?php

namespace App\Actions\Practice;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeStatusLog;
use App\Models\User;
use App\Services\NotificationManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdatePracticeAction
{
    public function __construct(private NotificationManager $notifications) {}

    public function execute(array $data, Practice $practice, int $userId, array $allData = []): Practice
    {
        $oldStatus = $practice->status;
        $oldAssignedUserIds = $practice->assignedUsers()->pluck('users.id')->map(fn ($id): int => (int) $id);

        DB::transaction(function () use ($allData, $data, $oldStatus, $practice, $userId): void {
            $updateData = Arr::except($data, ['user_ids']);

            if (array_key_exists('branch_id', $allData)) {
                $updateData['branch_id'] = $allData['branch_id'];
            }

            if (! empty($updateData)) {
                $practice->update($updateData);
            }

            if ($oldStatus !== $practice->status) {
                PracticeStatusLog::create([
                    'practice_id' => $practice->id,
                    'user_id' => $userId,
                    'old_status' => $oldStatus,
                    'new_status' => $practice->status,
                ]);
            }

            if (array_key_exists('user_ids', $data)) {
                $practice->assignedUsers()->sync($data['user_ids']);
            }

            if ($oldStatus !== 'completata' && $practice->status === 'completata') {
                $practice->deadlines()
                    ->whereNotIn('status', [PracticeDeadline::STATUS_COMPLETED, PracticeDeadline::STATUS_CANCELLED])
                    ->update(['status' => PracticeDeadline::STATUS_COMPLETED]);
            }
        });

        $practice->refresh()->load(['assignedUsers', 'client.user']);
        $newAssignedUserIds = $practice->assignedUsers->pluck('id')->map(fn ($id): int => (int) $id);
        $changedAssignedUserIds = $oldAssignedUserIds->diff($newAssignedUserIds)
            ->merge($newAssignedUserIds->diff($oldAssignedUserIds))
            ->unique();

        if ($changedAssignedUserIds->isNotEmpty()) {
            $this->notifications->send(
                User::query()->whereKey($changedAssignedUserIds)->get(),
                'practices.assigned',
                'practices',
                'Assegnazione pratica modificata',
                "È cambiata l’assegnazione della pratica #{$practice->id}.",
                $practice,
                route('practices.show', $practice, false),
                $userId,
            );
        }

        if ($oldStatus !== $practice->status) {
            $this->notifications->send(
                $practice->assignedUsers,
                'practices.status_changed',
                'practices',
                'Stato pratica aggiornato',
                "La pratica #{$practice->id} è passata da {$oldStatus} a {$practice->status}.",
                $practice,
                route('practices.show', $practice, false),
                $userId,
            );

            if ($practice->client?->user) {
                $this->notifications->send(
                    [$practice->client->user],
                    'practices.status_changed',
                    'practices',
                    'Stato pratica aggiornato',
                    "La tua pratica è ora nello stato {$practice->status}.",
                    $practice,
                    route('practice-status.index', absolute: false),
                    $userId,
                );
            }
        }

        return $practice;
    }
}
