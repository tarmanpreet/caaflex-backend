<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\Procedure;
use App\Models\ProcedureDeadlineTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employeeAssigned;

    protected User $employeeNotAssigned;

    protected Practice $practice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create employee assigned to practice
        $this->employeeAssigned = User::factory()->create();
        $this->employeeAssigned->assignRole('employee');

        // Create employee NOT assigned to practice
        $this->employeeNotAssigned = User::factory()->create();
        $this->employeeNotAssigned->assignRole('employee');

        // Create practice and assign employeeAssigned
        $this->practice = Practice::factory()->create();
        $this->practice->assignedUsers()->attach($this->employeeAssigned->id, ['assigned_at' => now()]);
    }

    public function test_admin_can_list_deadlines(): void
    {
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/practices/{$this->practice->id}/deadlines");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $deadline->id);
    }

    public function test_employee_can_list_own_practice_deadlines(): void
    {
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($this->employeeAssigned, 'api')
            ->getJson("/api/v1/practices/{$this->practice->id}/deadlines");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $deadline->id);
    }

    public function test_employee_cannot_list_other_practice_deadlines(): void
    {
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($this->employeeNotAssigned, 'api')
            ->getJson("/api/v1/practices/{$this->practice->id}/deadlines");

        $response->assertStatus(403);
    }

    public function test_employee_can_create_deadline_on_assigned_practice(): void
    {
        $deadlineData = [
            'title' => 'Test Deadline',
            'deadline_at' => now()->addDays(7)->toDateTimeString(),
            'notes' => 'Test notes',
            'priority' => PracticeDeadline::PRIORITY_HIGH,
            'user_id' => $this->employeeAssigned->id,
        ];

        $response = $this->actingAs($this->employeeAssigned, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", $deadlineData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Deadline');

        $this->assertDatabaseHas('practice_deadlines', [
            'practice_id' => $this->practice->id,
            'title' => 'Test Deadline',
            'created_by' => $this->employeeAssigned->id,
        ]);
    }

    public function test_procedure_steps_inherit_primary_deadline_assignee_and_use_day_hour_offsets(): void
    {
        $procedure = Procedure::factory()->create();
        ProcedureDeadlineTemplate::factory()->create([
            'procedure_id' => $procedure->id,
            'title' => 'Controllo preliminare',
            'offset_days' => 1,
            'offset_hours' => 2,
            'priority' => PracticeDeadline::PRIORITY_HIGH,
            'position' => 0,
        ]);
        ProcedureDeadlineTemplate::factory()->create([
            'procedure_id' => $procedure->id,
            'title' => 'Invio documentazione',
            'offset_days' => 0,
            'offset_hours' => 4,
            'priority' => PracticeDeadline::PRIORITY_URGENT,
            'position' => 1,
        ]);
        $this->practice->update(['procedure_id' => $procedure->id]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", [
                'title' => 'Scadenza principale',
                'deadline_at' => '2026-09-20 12:00:00',
                'priority' => PracticeDeadline::PRIORITY_MEDIUM,
                'user_id' => $this->employeeAssigned->id,
                'generate_procedure_steps' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.kind', PracticeDeadline::KIND_PROCEDURE_PRIMARY);

        $primary = PracticeDeadline::query()
            ->where('practice_id', $this->practice->id)
            ->where('kind', PracticeDeadline::KIND_PROCEDURE_PRIMARY)
            ->firstOrFail();

        $this->assertDatabaseHas('practice_deadlines', [
            'parent_deadline_id' => $primary->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
            'title' => 'Controllo preliminare',
            'deadline_at' => '2026-09-19 10:00:00',
            'advance_minutes' => 1560,
            'user_id' => $this->employeeAssigned->id,
        ]);
        $this->assertDatabaseHas('practice_deadlines', [
            'parent_deadline_id' => $primary->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
            'title' => 'Invio documentazione',
            'deadline_at' => '2026-09-20 08:00:00',
            'advance_minutes' => 240,
            'user_id' => $this->employeeAssigned->id,
        ]);
        $this->assertSame(2, $primary->steps()->count());
    }

    public function test_updating_primary_deadline_reschedules_and_reassigns_open_steps_only(): void
    {
        $newAssignee = User::factory()->create();
        $newAssignee->assignRole('admin');
        $primary = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_PRIMARY,
            'deadline_at' => '2026-09-20 12:00:00',
            'user_id' => $this->employeeAssigned->id,
        ]);
        $openStep = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
            'parent_deadline_id' => $primary->id,
            'deadline_at' => '2026-09-19 12:00:00',
            'advance_minutes' => 1440,
            'status' => PracticeDeadline::STATUS_PENDING,
            'user_id' => $this->employeeAssigned->id,
        ]);
        $completedStep = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'kind' => PracticeDeadline::KIND_PROCEDURE_STEP,
            'parent_deadline_id' => $primary->id,
            'deadline_at' => '2026-09-19 12:00:00',
            'advance_minutes' => 1440,
            'status' => PracticeDeadline::STATUS_COMPLETED,
            'user_id' => $this->employeeAssigned->id,
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/practices/{$this->practice->id}/deadlines/{$primary->id}", [
                'deadline_at' => '2026-09-25 15:00:00',
                'user_id' => $newAssignee->id,
            ])
            ->assertOk();

        $this->assertSame('2026-09-24 15:00:00', $openStep->fresh()->deadline_at->format('Y-m-d H:i:s'));
        $this->assertSame($newAssignee->id, $openStep->fresh()->user_id);
        $this->assertSame('2026-09-19 12:00:00', $completedStep->fresh()->deadline_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->employeeAssigned->id, $completedStep->fresh()->user_id);
    }

    public function test_admin_can_assign_deadline_to_unassigned_superadmin(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", [
                'title' => 'Scadenza superadmin',
                'deadline_at' => now()->addDays(7)->toDateTimeString(),
                'user_id' => $superadmin->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $superadmin->id);

        $this->assertDatabaseHas('practice_deadlines', [
            'practice_id' => $this->practice->id,
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_employee_cannot_create_deadline_on_unassigned_practice(): void
    {
        $deadlineData = [
            'title' => 'Test Deadline',
            'deadline_at' => now()->addDays(7)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->employeeNotAssigned, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", $deadlineData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('practice_deadlines', [
            'practice_id' => $this->practice->id,
            'title' => 'Test Deadline',
        ]);
    }

    public function test_employee_can_update_deadline_on_assigned_practice(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => PracticeDeadline::STATUS_IN_PROGRESS,
        ];

        $response = $this->actingAs($this->employeeAssigned, 'api')
            ->putJson("/api/v1/practices/{$this->practice->id}/deadlines/{$deadline->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('practice_deadlines', [
            'id' => $deadline->id,
            'title' => 'Updated Title',
            'status' => PracticeDeadline::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_assigned_employee_can_complete_step_directly(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->employeeAssigned)
            ->from(route('practices.show', $this->practice))
            ->patch(route('practices.deadlines.complete', [$this->practice, $deadline]))
            ->assertRedirect(route('practices.show', $this->practice))
            ->assertSessionHas('success', 'Step completato.');

        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $deadline->fresh()->status);
    }

    public function test_completing_an_already_completed_step_is_idempotent(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'status' => PracticeDeadline::STATUS_COMPLETED,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('practices.deadlines.complete', [$this->practice, $deadline]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Step completato.');

        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $deadline->fresh()->status);
    }

    public function test_unassigned_employee_cannot_complete_step(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $this->practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->employeeNotAssigned)
            ->patch(route('practices.deadlines.complete', [$this->practice, $deadline]))
            ->assertForbidden();

        $this->assertSame(PracticeDeadline::STATUS_PENDING, $deadline->fresh()->status);
    }

    public function test_step_from_another_practice_cannot_be_completed_through_nested_route(): void
    {
        $otherPractice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $otherPractice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('practices.deadlines.complete', [$this->practice, $deadline]))
            ->assertNotFound();

        $this->assertSame(PracticeDeadline::STATUS_PENDING, $deadline->fresh()->status);
    }

    public function test_admin_can_delete_deadline(): void
    {
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/practices/{$this->practice->id}/deadlines/{$deadline->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('practice_deadlines', [
            'id' => $deadline->id,
        ]);
    }

    public function test_employee_cannot_delete_deadline(): void
    {
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($this->employeeAssigned, 'api')
            ->deleteJson("/api/v1/practices/{$this->practice->id}/deadlines/{$deadline->id}");

        $response->assertStatus(403);

        $this->assertNotSoftDeleted('practice_deadlines', [
            'id' => $deadline->id,
        ]);
    }

    public function test_deadline_can_be_assigned_to_user_not_assigned_to_practice(): void
    {
        $deadlineData = [
            'title' => 'Test Deadline',
            'deadline_at' => now()->addDays(7)->toDateTimeString(),
            'user_id' => $this->employeeNotAssigned->id,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", $deadlineData);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $this->employeeNotAssigned->id);
    }

    public function test_validation_deadline_at_is_required(): void
    {
        $deadlineData = [
            'title' => 'Test Deadline',
            // deadline_at is missing
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", $deadlineData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline_at']);
    }
}
