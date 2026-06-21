<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeDocument;
use App\Models\PracticeNote;
use App\Models\PracticeStatusLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();
    }

    public function test_admin_dashboard_renders_real_data_payload(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'fiscal_code' => 'RSSMRA80A01H501U',
        ]);

        $activePractice = Practice::factory()->create([
            'client_profile_id' => $client->id,
            'type' => 'ISEE',
            'status' => 'in_lavorazione',
        ]);

        $waitingPractice = Practice::factory()->create([
            'client_profile_id' => $client->id,
            'type' => '730',
            'status' => 'in_attesa_documenti',
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $activePractice->id,
            'title' => 'Raccogli documenti',
            'deadline_at' => now()->addDay(),
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $waitingPractice->id,
            'title' => 'Invio finale',
            'deadline_at' => now()->subDays(2),
            'status' => PracticeDeadline::STATUS_COMPLETED,
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $waitingPractice->id,
            'title' => 'Deadline annullata',
            'deadline_at' => now()->subDays(1),
            'status' => PracticeDeadline::STATUS_CANCELLED,
        ]);

        Appointment::factory()->create([
            'assigned_user_id' => $admin->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'da_confermare',
        ]);

        PracticeDocument::factory()->create([
            'practice_id' => $activePractice->id,
            'uploaded_by' => $admin->id,
            'original_name' => 'isee.pdf',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        PracticeNote::factory()->create([
            'practice_id' => $activePractice->id,
            'user_id' => $admin->id,
            'body' => 'Richiesto aggiornamento dati.',
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);

        PracticeStatusLog::factory()->create([
            'practice_id' => $waitingPractice->id,
            'user_id' => $admin->id,
            'old_status' => 'nuova',
            'new_status' => 'in_attesa_documenti',
            'created_at' => now()->subMinutes(15),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats', 3)
                ->where('stats.0.value', 2)
                ->where('stats.1.value', 1)
                ->where('stats.2.value', 1)
                ->where('efficiency.value', 100)
                ->where('efficiency.completed', 1)
                ->where('efficiency.total', 1)
                ->has('deadlines', 1)
                ->has('activities', 3)
                ->has('practices', 2)
            );
    }

    public function test_employee_dashboard_is_scoped_to_assigned_records(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('employee');

        $visibleClient = ClientProfile::factory()->create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);
        $hiddenClient = ClientProfile::factory()->create(['first_name' => 'Anna', 'last_name' => 'Verdi']);

        $visiblePractice = Practice::factory()->create([
            'client_profile_id' => $visibleClient->id,
            'status' => 'in_lavorazione',
            'type' => 'ISEE',
        ]);
        $visiblePractice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);

        $hiddenPractice = Practice::factory()->create([
            'client_profile_id' => $hiddenClient->id,
            'status' => 'in_lavorazione',
            'type' => '730',
        ]);
        $hiddenPractice->assignedUsers()->attach($otherEmployee->id, ['assigned_at' => now()]);

        PracticeDeadline::factory()->create([
            'practice_id' => $visiblePractice->id,
            'title' => 'Scadenza visibile',
            'status' => PracticeDeadline::STATUS_PENDING,
            'deadline_at' => now()->addDay(),
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $hiddenPractice->id,
            'title' => 'Scadenza nascosta',
            'status' => PracticeDeadline::STATUS_PENDING,
            'deadline_at' => now()->addDay(),
        ]);

        Appointment::factory()->create([
            'assigned_user_id' => $employee->id,
            'status' => 'da_confermare',
            'scheduled_at' => now()->addDay(),
        ]);

        Appointment::factory()->create([
            'assigned_user_id' => $otherEmployee->id,
            'status' => 'da_confermare',
            'scheduled_at' => now()->addDay(),
        ]);

        PracticeDocument::factory()->create([
            'practice_id' => $visiblePractice->id,
            'uploaded_by' => $employee->id,
            'original_name' => 'visibile.pdf',
        ]);

        PracticeDocument::factory()->create([
            'practice_id' => $hiddenPractice->id,
            'uploaded_by' => $otherEmployee->id,
            'original_name' => 'nascosto.pdf',
        ]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.0.value', 1)
                ->where('stats.1.value', 1)
                ->where('stats.2.value', 1)
                ->has('deadlines', 1)
                ->where('deadlines.0.title', 'Scadenza visibile')
                ->has('activities', 1)
                ->where('activities.0.title', 'visibile.pdf')
                ->has('practices', 1)
                ->where('practices.0.client_name', 'Luca Bianchi')
            );
    }

    public function test_dashboard_handles_empty_state_payloads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats', 3)
                ->where('stats.0.value', 0)
                ->where('stats.1.value', 0)
                ->where('stats.2.value', 0)
                ->where('efficiency.value', 0)
                ->where('efficiency.completed', 0)
                ->where('efficiency.total', 0)
                ->has('deadlines', 0)
                ->has('activities', 0)
                ->has('practices', 0)
            );
    }

    public function test_admin_can_fetch_dashboard_notices_api(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $practice = Practice::factory()->create([
            'client_profile_id' => $client->id,
            'type' => '730',
            'status' => 'in_lavorazione',
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'title' => 'Scadenza moduli 730',
            'deadline_at' => now()->addDay(),
            'status' => PracticeDeadline::STATUS_PENDING,
            'priority' => PracticeDeadline::PRIORITY_HIGH,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/dashboard/notices');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Scadenza moduli 730')
            ->assertJsonPath('data.0.priority', 'high')
            ->assertJsonPath('data.0.target_route', '/(operator)/practices');
    }

    public function test_employee_dashboard_notices_are_scoped(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');

        $otherEmployee = User::factory()->create(['is_active' => true]);
        $otherEmployee->assignRole('employee');

        $visiblePractice = Practice::factory()->create([
            'status' => 'in_lavorazione',
            'type' => 'ISEE',
        ]);
        $visiblePractice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);

        $hiddenPractice = Practice::factory()->create([
            'status' => 'in_lavorazione',
            'type' => '730',
        ]);
        $hiddenPractice->assignedUsers()->attach($otherEmployee->id, ['assigned_at' => now()]);

        PracticeDeadline::factory()->create([
            'practice_id' => $visiblePractice->id,
            'title' => 'Visibile',
            'status' => PracticeDeadline::STATUS_PENDING,
            'priority' => PracticeDeadline::PRIORITY_URGENT,
            'deadline_at' => now()->addDay(),
        ]);

        PracticeDeadline::factory()->create([
            'practice_id' => $hiddenPractice->id,
            'title' => 'Nascosta',
            'status' => PracticeDeadline::STATUS_PENDING,
            'priority' => PracticeDeadline::PRIORITY_URGENT,
            'deadline_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($employee, 'api')
            ->getJson('/api/v1/dashboard/notices');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Visibile');
    }
}
