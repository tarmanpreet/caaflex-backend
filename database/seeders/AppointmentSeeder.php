<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use App\Models\UserAvailability;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPracticeTypes();
        $this->seedUserAvailabilities();
        $this->seedAppointments();
    }

    private function seedPracticeTypes(): void
    {
        $types = [
            ['name' => '730 - Dichiarazione dei Redditi', 'duration_minutes' => 60, 'color' => '#3B82F6'],
            ['name' => 'ISEE - Attestazione',             'duration_minutes' => 30, 'color' => '#10B981'],
            ['name' => 'Successione',                     'duration_minutes' => 90, 'color' => '#F59E0B'],
            ['name' => 'IMU/TASI',                        'duration_minutes' => 30, 'color' => '#8B5CF6'],
            ['name' => 'RED - Redditi Pensionati',        'duration_minutes' => 45, 'color' => '#EC4899'],
            ['name' => 'Bonus Edilizi',                   'duration_minutes' => 60, 'color' => '#14B8A6'],
        ];

        foreach ($types as $type) {
            PracticeType::firstOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedUserAvailabilities(): void
    {
        $employees = User::role('employee')->get();

        $slots = [
            ['day_of_week' => 1, 'time_from' => '09:00', 'time_to' => '17:00'],
            ['day_of_week' => 3, 'time_from' => '09:00', 'time_to' => '12:00'],
            ['day_of_week' => 5, 'time_from' => '09:00', 'time_to' => '12:00'],
        ];

        foreach ($employees as $employee) {
            foreach ($slots as $slot) {
                UserAvailability::updateOrCreate(
                    [
                        'user_id'     => $employee->id,
                        'day_of_week' => $slot['day_of_week'],
                    ],
                    [
                        'time_from' => $slot['time_from'],
                        'time_to'   => $slot['time_to'],
                    ]
                );
            }
        }
    }

    private function seedAppointments(): void
    {
        $admin         = User::where('email', env('SEED_ADMIN_EMAIL', 'admin@email.com'))->first();
        $clients       = ClientProfile::all();
        $employees     = User::role('employee')->get();
        $practiceTypes = PracticeType::all();

        if ($clients->isEmpty() || $employees->isEmpty() || $practiceTypes->isEmpty()) {
            $this->command->warn('Skipping appointments: missing clients, employees, or practice types.');
            return;
        }

        $typeMap = [
            '730 - Dichiarazione dei Redditi' => '730',
            'ISEE - Attestazione'             => 'ISEE',
            'Successione'                     => 'SUCCESSIONE',
            'IMU/TASI'                        => 'IMU_TASI',
            'RED - Redditi Pensionati'        => 'RED_INPS',
            'Bonus Edilizi'                   => 'BONUS_AGEVOLAZIONI',
        ];

        $notes = [
            'Cliente richiede urgenza per scadenza imminente.',
            'Portare documentazione completa al prossimo incontro.',
            'Verificare situazione catastale prima dell\'appuntamento.',
            'Cliente con ISEE precedente da aggiornare.',
            'Necessaria delega firmata dal coniuge.',
            'Appuntamento riprogrammato su richiesta del cliente.',
            'Da verificare posizione contributiva INPS.',
            'Seconda consulenza per pratica complessa.',
            null, null, null, null,
        ];

        $appointments = [
            ['days' => -30, 'hour' => '09:00', 'status' => 'completato'],
            ['days' => -25, 'hour' => '10:00', 'status' => 'completato'],
            ['days' => -20, 'hour' => '14:00', 'status' => 'completato'],
            ['days' => -15, 'hour' => '11:00', 'status' => 'completato'],
            ['days' => -10, 'hour' => '09:30', 'status' => 'completato'],
            ['days' => -22, 'hour' => '15:00', 'status' => 'cancellato'],
            ['days' => -18, 'hour' => '10:00', 'status' => 'cancellato'],
            ['days' => -12, 'hour' => '14:30', 'status' => 'cancellato'],
            ['days' => -3,  'hour' => '09:00', 'status' => 'confermato'],
            ['days' => -1,  'hour' => '11:00', 'status' => 'confermato'],
            ['days' => 2,   'hour' => '09:00', 'status' => 'confermato'],
            ['days' => 5,   'hour' => '10:30', 'status' => 'confermato'],
            ['days' => 7,   'hour' => '14:00', 'status' => 'confermato'],
            ['days' => 10,  'hour' => '09:00', 'status' => 'confermato'],
            ['days' => 14,  'hour' => '15:00', 'status' => 'confermato'],
            ['days' => 3,   'hour' => '10:00', 'status' => 'da_confermare'],
            ['days' => 6,   'hour' => '11:00', 'status' => 'da_confermare'],
            ['days' => 8,   'hour' => '09:00', 'status' => 'da_confermare'],
            ['days' => 12,  'hour' => '14:00', 'status' => 'da_confermare'],
            ['days' => 15,  'hour' => '10:00', 'status' => 'da_confermare'],
        ];

        foreach ($appointments as $i => $data) {
            $practiceType = $practiceTypes[$i % $practiceTypes->count()];
            $client       = $clients[$i % $clients->count()];
            $employee     = $employees[$i % $employees->count()];
            $scheduledAt  = Carbon::now()->addDays($data['days'])->setTimeFromTimeString($data['hour']);

            $appointment = Appointment::create([
                'client_profile_id' => $client->id,
                'assigned_user_id'  => $employee->id,
                'practice_type_id'  => $practiceType->id,
                'scheduled_at'      => $scheduledAt,
                'duration_minutes'  => $practiceType->duration_minutes,
                'status'            => $data['status'],
                'notes'             => $notes[$i % count($notes)],
                'created_by'        => $admin->id,
            ]);

            if ($data['status'] === 'confermato') {
                $practiceTypeName = $typeMap[$practiceType->name] ?? 'ALTRO';

                $practice = Practice::create([
                    'client_profile_id' => $client->id,
                    'type'              => $practiceTypeName,
                    'practice_type_id'  => $practiceType->id,
                    'status'            => 'in_lavorazione',
                    'created_by'        => $admin->id,
                ]);

                $appointment->update(['practice_id' => $practice->id]);
            }
        }
    }
}
