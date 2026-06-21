<?php

namespace App\Actions\Practice;

use App\Models\Practice;
use App\Models\PracticeStatusLog;
use Illuminate\Support\Arr;

class StorePracticeAction
{
    public function execute(array $data, int $createdBy): Practice
    {
        $practice = Practice::create(
            Arr::except($data, ['user_ids']) + [
                'status'     => $data['status'] ?? 'nuova',
                'created_by' => $createdBy,
            ]
        );

        $practice->assignedUsers()->sync($data['user_ids'] ?? []);

        PracticeStatusLog::create([
            'practice_id' => $practice->id,
            'user_id'     => $createdBy,
            'old_status'  => null,
            'new_status'  => $practice->status,
        ]);

        return $practice;
    }
}
