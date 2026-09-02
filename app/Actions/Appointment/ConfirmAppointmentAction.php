<?php

namespace App\Actions\Appointment;

use App\Mail\AppointmentConfirmedMail;
use App\Models\Appointment;
use App\Models\Practice;
use Illuminate\Support\Facades\Mail;

class ConfirmAppointmentAction
{
    public function execute(Appointment $appointment, int $userId): void
    {
        $appointment->loadMissing(['client.user', 'assignedUser', 'practiceType']);

        if ($appointment->practice_id === null && $appointment->practice_type_id !== null) {
            $practice = Practice::create([
                'client_profile_id' => $appointment->client_profile_id,
                'branch_id' => $appointment->branch_id ?? $appointment->client?->branch_id,
                'type' => $appointment->practiceType->name,
                'practice_type_id' => $appointment->practice_type_id,
                'status' => 'nuova',
                'reference_year' => now()->year,
                'created_by' => $userId,
                'notes' => 'Pratica creata automaticamente dalla conferma appuntamento #'.$appointment->id,
            ]);

            if ($appointment->assigned_user_id !== null) {
                $practice->assignedUsers()->syncWithoutDetaching([$appointment->assigned_user_id]);
            }

            $appointment->update(['practice_id' => $practice->id]);
        }

        if ($appointment->client->email) {
            Mail::queue(new AppointmentConfirmedMail($appointment, 'client'));
        }

        if ($appointment->assignedUser) {
            Mail::queue(new AppointmentConfirmedMail($appointment, 'user'));
        }
    }
}
