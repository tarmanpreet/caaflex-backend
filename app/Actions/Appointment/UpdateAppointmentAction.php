<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;

class UpdateAppointmentAction
{
    public function __construct(
        protected ConfirmAppointmentAction $confirmAction,
    ) {}

    public function execute(array $data, Appointment $appointment, int $userId, array $allData = []): Appointment
    {
        $oldStatus = $appointment->status;

        $filteredData = array_filter($data, fn ($v) => ! is_null($v));

        // Preserve branch_id even if null (validation removes nullable fields)
        if (array_key_exists('branch_id', $allData)) {
            $filteredData['branch_id'] = $allData['branch_id'];
        }

        $appointment->update($filteredData);
        $appointment->refresh();

        if ($oldStatus !== 'confermato' && $appointment->status === 'confermato') {
            $this->confirmAction->execute($appointment, $userId);
            $appointment->refresh();
        }

        return $appointment;
    }
}
