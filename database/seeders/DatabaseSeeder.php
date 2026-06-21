<?php

namespace Database\Seeders;

use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeNote;
use App\Models\PracticeStatusLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(ProcedureSeeder::class);

        if (app()->environment('local')) {
            $this->seedLocalUsers();
            $this->seedFakeData();
        }
    }

    private function seedLocalUsers(): void
    {
        $users = [
            [
                'name'  => 'Super Admin',
                'email' => env('SEED_SUPERADMIN_EMAIL', 'superadmin@email.com'),
                'role'  => 'superadmin',
            ],
            [
                'name'  => 'Admin',
                'email' => env('SEED_ADMIN_EMAIL', 'admin@email.com'),
                'role'  => 'admin',
            ],
            [
                'name'  => 'Employee',
                'email' => env('SEED_EMPLOYEE_EMAIL', 'employee@email.com'),
                'role'  => 'employee',
            ],
            [
                'name'  => 'Client',
                'email' => env('SEED_CLIENT_EMAIL', 'client@email.com'),
                'role'  => 'cliente',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles($data['role']);
        }
    }

    private function seedFakeData(): void
    {
        $admin    = User::where('email', env('SEED_ADMIN_EMAIL', 'admin@email.com'))->first();
        $employee = User::where('email', env('SEED_EMPLOYEE_EMAIL', 'employee@email.com'))->first();
        $client   = User::where('email', env('SEED_CLIENT_EMAIL', 'client@email.com'))->first();

        // 3 employee aggiuntivi finti
        $employees = User::factory(3)->create(['password' => Hash::make('password')]);
        $employees->each(fn ($u) => $u->assignRole('employee'));
        $employees->push($employee);

        // 10 clienti — uno è l'utente client reale, gli altri sono profili anonimi
        $clientProfile = ClientProfile::factory()->create([
            'user_id'    => $client->id,
            'created_by' => $admin->id,
        ]);

        $otherProfiles = ClientProfile::factory(9)->create([
            'created_by' => $admin->id,
        ]);

        $allProfiles = $otherProfiles->push($clientProfile);

        // Documenti per ogni cliente (0-2 documenti ciascuno)
        $allProfiles->each(function ($profile) use ($admin) {
            ClientDocument::factory(fake()->numberBetween(0, 2))->create([
                'client_profile_id' => $profile->id,
                'uploaded_by'       => $admin->id,
            ]);
        });

        // 15 pratiche distribuite tra i clienti
        $practices = Practice::factory(15)->create([
            'client_profile_id' => fn () => $allProfiles->random()->id,
        ]);

        // Assegna 1-2 employee ad ogni pratica
        $practices->each(function ($practice) use ($employees, $admin) {
            $assigned = $employees->random(fake()->numberBetween(1, 2));
            $practice->assignedUsers()->attach($assigned->pluck('id'));

            // 0-3 note per pratica
            PracticeNote::factory(fake()->numberBetween(0, 3))->create([
                'practice_id' => $practice->id,
                'user_id'     => $assigned->first()->id,
            ]);

            // 0-2 documenti per pratica
            PracticeDocument::factory(fake()->numberBetween(0, 2))->create([
                'practice_id' => $practice->id,
                'uploaded_by' => $admin->id,
            ]);

            // 1-2 log di stato
            PracticeStatusLog::factory(fake()->numberBetween(1, 2))->create([
                'practice_id' => $practice->id,
                'user_id'     => $admin->id,
            ]);
        });

        $this->call(AppointmentSeeder::class);
    }
}
