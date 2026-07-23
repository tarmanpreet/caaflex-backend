<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\User;
use App\Services\NotificationManager;

class UpdateAppointmentAction
{
    public function __construct(
        protected ConfirmAppointmentAction $confirmAction,
        protected NotificationManager $notifications,
    ) {}

    public function execute(array $data, Appointment $appointment, int $userId, array $allData = []): Appointment
    {
        $oldStatus = $appointment->status;
        $oldAssignedUserId = $appointment->assigned_user_id;
        $oldScheduledAt = $appointment->scheduled_at?->copy();

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

        $appointment->loadMissing(['client.user', 'assignedUser', 'practiceType']);
        $actionUrl = route('appointments.show', $appointment, false);
        $baseRecipients = collect([$appointment->assignedUser, $appointment->client?->user])->filter();
        $clientName = trim($appointment->client->first_name.' '.$appointment->client->last_name);

        if ($oldAssignedUserId !== $appointment->assigned_user_id) {
            $oldAssignee = $oldAssignedUserId ? User::query()->find($oldAssignedUserId) : null;
            $this->notifications->send(
                $baseRecipients->push($oldAssignee)->filter(),
                'appointments.assigned',
                'appointments',
                'Assegnazione appuntamento modificata',
                "È cambiato l’assegnatario dell’appuntamento di {$clientName}.",
                $appointment,
                $actionUrl,
                $userId,
            );
        }

        if ($oldStatus !== $appointment->status) {
            $this->notifications->send(
                $baseRecipients,
                'appointments.status_changed',
                'appointments',
                'Stato appuntamento aggiornato',
                "L’appuntamento di {$clientName} è passato da {$oldStatus} a {$appointment->status}.",
                $appointment,
                $actionUrl,
                $userId,
            );
        }

        if ($oldScheduledAt && ! $oldScheduledAt->equalTo($appointment->scheduled_at)) {
            $this->notifications->send(
                $baseRecipients,
                'appointments.rescheduled',
                'appointments',
                'Appuntamento riprogrammato',
                "L’appuntamento di {$clientName} è stato riprogrammato.",
                $appointment,
                $actionUrl,
                $userId,
                'warning',
            );
        }

        return $appointment;
    }
}
