<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\Practice;
use Illuminate\Support\Facades\Mail;

class ConfirmAppointmentAction
{
    public function execute(Appointment $appointment, int $userId): void
    {
        if ($appointment->practice_id === null && $appointment->practice_type_id !== null) {
            $practice = Practice::create([
                'client_profile_id' => $appointment->client_profile_id,
                'type' => $appointment->practiceType->name,
                'practice_type_id' => $appointment->practice_type_id,
                'status' => 'nuova',
                'reference_year' => now()->year,
                'created_by' => $userId,
                'notes' => 'Pratica creata automaticamente dalla conferma appuntamento #'.$appointment->id,
            ]);
            $appointment->update(['practice_id' => $practice->id]);
        }

        $appointment->loadMissing(['client', 'assignedUser', 'practiceType']);

        if ($appointment->client->email) {
            Mail::queue(new \App\Mail\AppointmentConfirmedMail($appointment, 'client'));
        }

        if ($appointment->assignedUser) {
            Mail::queue(new \App\Mail\AppointmentConfirmedMail($appointment, 'user'));
        }
    }
}
