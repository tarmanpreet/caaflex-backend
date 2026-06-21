<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\AutoConfirmSlot;

class StoreAppointmentAction
{
    public function __construct(
        protected ConfirmAppointmentAction $confirmAction,
    ) {}

    public function execute(array $data, int $createdBy): Appointment
    {
        $status = $this->determineStatus($data);

        $appointment = Appointment::create(array_merge($data, [
            'status' => $status,
            'created_by' => $createdBy,
        ]));

        if ($status === 'confermato') {
            $appointment->loadMissing(['client', 'assignedUser', 'practiceType']);
            $this->confirmAction->execute($appointment, $createdBy);
        }

        return $appointment->refresh();
    }

    protected function determineStatus(array $data): string
    {
        $scheduledAt = $data['scheduled_at'] ?? null;

        if (! $scheduledAt) {
            return 'da_confermare';
        }

        $dateTime = is_string($scheduledAt) ? \Carbon\Carbon::parse($scheduledAt) : $scheduledAt;

        $slot = AutoConfirmSlot::where('day_of_week', $dateTime->dayOfWeek)
            ->first();

        if (! $slot) {
            return 'da_confermare';
        }

        $timeString = $dateTime->format('H:i:s');

        return $timeString >= $slot->time_from && $timeString < $slot->time_to
            ? 'confermato'
            : 'da_confermare';
    }
}
