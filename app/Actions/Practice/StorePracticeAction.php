<?php

namespace App\Actions\Practice;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeStatusLog;
use App\Services\NotificationManager;
use Illuminate\Support\Arr;

class StorePracticeAction
{
    public function __construct(private NotificationManager $notifications) {}

    public function execute(array $data, int $createdBy): Practice
    {
        $data['branch_id'] = ClientProfile::query()->findOrFail($data['client_profile_id'])->branch_id;

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
}
