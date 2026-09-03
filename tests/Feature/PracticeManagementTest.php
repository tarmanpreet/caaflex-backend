<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\ProcedureDeadlineTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PracticeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();
    }

    public function test_admin_can_view_practice_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Practice::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/practices')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Index')
                ->has('practices')
                ->has('summary')
            );
    }

    public function test_summary_is_independent_of_status_filter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Practice::factory()->create(['status' => 'in_lavorazione']);
        Practice::factory()->create(['status' => 'in_lavorazione']);
        Practice::factory()->create(['status' => 'completata']);
        Practice::factory()->create(['status' => 'in_attesa_documenti']);

        $this->actingAs($admin)
            ->get('/practices?status=completata')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Index')
                ->where('summary.total', 4)
                ->where('summary.active', 2)
                ->where('summary.pending', 1)
                ->where('summary.complete', 1)
                ->has('practices.data', 1)
            );
    }

    public function test_admin_can_filter_practices_by_configured_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $selectedType = PracticeType::factory()->create(['name' => 'ISEE universitario']);
        $otherType = PracticeType::factory()->create(['name' => 'Dichiarazione IMU']);
        $selectedPractice = Practice::factory()->create([
            'type' => 'ALTRO',
            'practice_type_id' => $selectedType->id,
        ]);
        Practice::factory()->create([
            'type' => 'ALTRO',
            'practice_type_id' => $otherType->id,
        ]);

        $this->actingAs($admin)
            ->get('/practices?practice_type_id='.$selectedType->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Index')
                ->where('filters.practice_type_id', (string) $selectedType->id)
                ->has('practiceTypes', 2)
                ->has('practices.data', 1)
                ->where('practices.data.0.id', $selectedPractice->id)
            );
    }

    public function test_employee_sees_only_assigned_practices(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        // Create 3 practices, assign only 1 to employee
        $assigned = Practice::factory()->create();
        $assigned->assignedUsers()->attach($employee->id);

        Practice::factory()->count(2)->create();

        $response = $this->actingAs($employee)
            ->get('/practices');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Index')
                ->has('practices.data', 1)
            );
    }

    public function test_cliente_role_cannot_access_practices(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)
            ->get('/practices')
            ->assertStatus(403);
    }

    public function test_admin_can_create_practice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create();

        $payload = [
            'client_profile_id' => $client->id,
            'type' => '730',
            'reference_year' => 2025,
            'notes' => 'Test practice notes',
        ];

        $response = $this->actingAs($admin)
            ->post('/practices', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $client->id,
            'type' => '730',
            'status' => 'nuova',
            'created_by' => $admin->id,
        ]);
    }

    public function test_creating_practice_generates_configured_procedure_deadlines(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create(['name' => 'Pratica con step']);
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Procedura automatica',
        ]);
        $template = ProcedureDeadlineTemplate::factory()->create([
            'procedure_id' => $procedure->id,
            'title' => 'Controllo documenti',
            'offset_days' => 1,
            'offset_hours' => 2,
            'priority' => PracticeDeadline::PRIORITY_HIGH,
        ]);

        $this->actingAs($admin)
            ->post(route('practices.store'), [
                'client_profile_id' => $client->id,
                'type' => $practiceType->name,
                'procedure_id' => $procedure->id,
                'deadline_at' => '2026-09-20 12:00:00',
                'user_ids' => [$admin->id],
            ])
            ->assertRedirect();

        $practice = Practice::query()->whereBelongsTo($client, 'client')->firstOrFail();
        $primaryDeadline = $practice->deadlines()
            ->where('kind', PracticeDeadline::KIND_PROCEDURE_PRIMARY)
            ->firstOrFail();

        $this->assertSame($admin->id, $primaryDeadline->user_id);
        $this->assertSame('2026-09-20 12:00:00', $primaryDeadline->deadline_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('practice_deadlines', [
            'practice_id' => $practice->id,
            'parent_deadline_id' => $primaryDeadline->id,
            'procedure_deadline_template_id' => $template->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
            'title' => 'Controllo documenti',
            'deadline_at' => '2026-09-19 10:00:00',
            'user_id' => $admin->id,
        ]);
        $this->assertSame(2, $practice->deadlines()->count());
    }

    public function test_procedure_deadline_days_are_calculated_on_the_server_when_creating_practice(): void
    {
        Carbon::setTestNow('2026-09-03 09:30:00');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create(['name' => 'Pratica con scadenza calcolata']);
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Procedura dieci giorni',
            'deadline_days' => 10,
        ]);
        ProcedureDeadlineTemplate::factory()->create([
            'procedure_id' => $procedure->id,
            'offset_days' => 1,
            'offset_hours' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('practices.store'), [
                'client_profile_id' => $client->id,
                'type' => $practiceType->name,
                'procedure_id' => $procedure->id,
                'user_ids' => [$admin->id],
            ])
            ->assertRedirect();

        $practice = Practice::query()->whereBelongsTo($client, 'client')->firstOrFail();
        $primaryDeadline = $practice->deadlines()
            ->where('kind', PracticeDeadline::KIND_PROCEDURE_PRIMARY)
            ->firstOrFail();

        $this->assertSame('2026-09-13 09:30:00', $practice->deadline_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-13 09:30:00', $primaryDeadline->deadline_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-12 09:30:00', $primaryDeadline->steps()->firstOrFail()->deadline_at->format('Y-m-d H:i:s'));
    }

    public function test_procedure_steps_are_not_generated_without_a_main_deadline(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create(['name' => 'Pratica senza scadenza']);
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $practiceType->id,
            'deadline_days' => null,
        ]);
        ProcedureDeadlineTemplate::factory()->create(['procedure_id' => $procedure->id]);

        $this->actingAs($admin)
            ->post(route('practices.store'), [
                'client_profile_id' => $client->id,
                'type' => $practiceType->name,
                'procedure_id' => $procedure->id,
                'user_ids' => [$admin->id],
            ])
            ->assertRedirect();

        $practice = Practice::query()->whereBelongsTo($client, 'client')->firstOrFail();

        $this->assertNull($practice->deadline_at);
        $this->assertSame(0, $practice->deadlines()->count());
    }

    public function test_practice_forms_offer_every_assignable_role(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Admin']);
        $viewer->assignRole('admin');

        foreach ([
            'Employee User' => 'employee',
            'Admin User' => 'admin',
            'Superadmin User' => 'superadmin',
        ] as $name => $role) {
            $user = User::factory()->create(['name' => $name]);
            $user->assignRole($role);
        }

        $client = User::factory()->create(['name' => 'Client User']);
        $client->assignRole('cliente');
        $practice = Practice::factory()->create();

        $expectedNames = ['Admin User', 'Employee User', 'Superadmin User', 'Viewer Admin'];

        $this->actingAs($viewer)
            ->get(route('practices.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Practices/Create')
                ->where('users', fn ($users): bool => $users->pluck('name')->all() === $expectedNames)
            );

        $this->actingAs($viewer)
            ->get(route('practices.show', $practice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Practices/Show')
                ->where('users', fn ($users): bool => $users->pluck('name')->all() === $expectedNames)
            );
    }

    public function test_admin_can_create_practice_with_a_configured_practice_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create(['name' => 'Dichiarazione IMU personalizzata']);

        $this->actingAs($admin)
            ->post('/practices', [
                'client_profile_id' => $client->id,
                'type' => $practiceType->name,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $client->id,
            'type' => $practiceType->name,
            'practice_type_id' => $practiceType->id,
        ]);
    }

    public function test_status_log_created_on_store(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create();

        $this->actingAs($admin)
            ->post('/practices', [
                'client_profile_id' => $client->id,
                'type' => 'ISEE',
            ]);

        $practice = Practice::where('type', 'ISEE')->first();

        $this->assertDatabaseHas('practice_status_logs', [
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'old_status' => null,
            'new_status' => 'nuova',
        ]);
    }

    public function test_admin_can_delete_practice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $response = $this->actingAs($admin)
            ->delete('/practices/'.$practice->id);

        $response->assertRedirect(route('practices.index'));

        $this->assertDatabaseMissing('practices', [
            'id' => $practice->id,
        ]);
    }

    public function test_employee_cannot_delete_practice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $practice = Practice::factory()->create();

        $this->actingAs($employee)
            ->delete('/practices/'.$practice->id)
            ->assertStatus(403);
    }

    public function test_show_page_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $this->actingAs($admin)
            ->get('/practices/'.$practice->id)
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Show')
            );
    }

    public function test_status_log_created_on_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create(['status' => 'nuova']);

        $this->actingAs($admin)
            ->put('/practices/'.$practice->id, [
                'status' => 'in_lavorazione',
            ]);

        $this->assertDatabaseHas('practice_status_logs', [
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'old_status' => 'nuova',
            'new_status' => 'in_lavorazione',
        ]);
    }

    public function test_completing_practice_completes_all_open_deadlines(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create(['status' => 'in_lavorazione']);
        $pending = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);
        $inProgress = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_IN_PROGRESS,
        ]);
        $cancelled = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_CANCELLED,
        ]);

        $this->actingAs($admin)
            ->from(route('practices.show', $practice))
            ->put(route('practices.update', $practice), ['status' => 'completata'])
            ->assertRedirect(route('practices.show', $practice))
            ->assertSessionHas('success', 'Pratica aggiornata correttamente.');

        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $pending->fresh()->status);
        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $inProgress->fresh()->status);
        $this->assertSame(PracticeDeadline::STATUS_CANCELLED, $cancelled->fresh()->status);
    }

    public function test_updating_practice_without_completing_it_keeps_deadlines_open(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create(['status' => 'nuova']);
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->from(route('practices.show', $practice))
            ->put(route('practices.update', $practice), ['status' => 'in_lavorazione'])
            ->assertRedirect(route('practices.show', $practice));

        $this->assertSame(PracticeDeadline::STATUS_PENDING, $deadline->fresh()->status);
    }

    public function test_admin_can_assign_users_on_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee1 = User::factory()->create();
        $employee1->assignRole('employee');

        $employee2 = User::factory()->create();
        $employee2->assignRole('employee');

        $practice = Practice::factory()->create();

        $this->actingAs($admin)
            ->put('/practices/'.$practice->id, [
                'user_ids' => [$employee1->id, $employee2->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('practice_user', [
            'practice_id' => $practice->id,
            'user_id' => $employee1->id,
        ]);
        $this->assertDatabaseHas('practice_user', [
            'practice_id' => $practice->id,
            'user_id' => $employee2->id,
        ]);
        $this->assertCount(2, $practice->fresh()->assignedUsers);
    }

    public function test_update_replaces_existing_assigned_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee1 = User::factory()->create();
        $employee1->assignRole('employee');

        $employee2 = User::factory()->create();
        $employee2->assignRole('employee');

        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($employee1->id, ['assigned_at' => now()]);

        // Replace with only employee2
        $this->actingAs($admin)
            ->put('/practices/'.$practice->id, [
                'user_ids' => [$employee2->id],
            ])
            ->assertRedirect();

        $assigned = $practice->fresh()->assignedUsers->pluck('id')->toArray();
        $this->assertNotContains($employee1->id, $assigned);
        $this->assertContains($employee2->id, $assigned);
        $this->assertCount(1, $assigned);
    }

    public function test_update_can_remove_all_assigned_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);

        $this->actingAs($admin)
            ->put('/practices/'.$practice->id, [
                'user_ids' => [],
            ])
            ->assertRedirect();

        $this->assertCount(0, $practice->fresh()->assignedUsers);
    }

    public function test_employee_without_assign_permission_cannot_change_assignments(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $target = User::factory()->create();
        $target->assignRole('employee');

        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);

        // Employee has practices.update (can edit fields) but NOT practices.assign
        // The PUT succeeds (302) but user_ids sync is silently skipped
        $this->actingAs($employee)
            ->put('/practices/'.$practice->id, [
                'type' => $practice->type,
                'status' => $practice->status,
                'user_ids' => [$target->id],
            ])
            ->assertRedirect();

        // Assignments unchanged: employee still assigned, target NOT added
        $assigned = $practice->fresh()->assignedUsers->pluck('id')->toArray();
        $this->assertContains($employee->id, $assigned);
        $this->assertNotContains($target->id, $assigned);
    }

    public function test_employee_without_assign_permission_cannot_change_assignments_through_api(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $target = User::factory()->create();
        $target->assignRole('employee');
        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);

        $this->actingAs($employee, 'api')
            ->putJson('/api/v1/practices/'.$practice->id, [
                'type' => $practice->type,
                'status' => $practice->status,
                'user_ids' => [$target->id],
            ])
            ->assertOk();

        $assignedUserIds = $practice->fresh()->assignedUsers->pluck('id');

        $this->assertTrue($assignedUserIds->contains($employee->id));
        $this->assertFalse($assignedUserIds->contains($target->id));
    }
}
