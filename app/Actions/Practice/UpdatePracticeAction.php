<?php

namespace App\Actions\Practice;

use App\Models\Practice;
use App\Models\PracticeStatusLog;
use Illuminate\Support\Arr;

class UpdatePracticeAction
{
    public function execute(array $data, Practice $practice, int $userId, array $allData = []): Practice
    {
        $oldStatus = $practice->status;

        $updateData = Arr::except($data, ['user_ids']);

        // Preserve branch_id even if null (validation removes nullable fields)
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

        return $practice;
    }
}
