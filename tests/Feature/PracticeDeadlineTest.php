<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\PracticeDeadline;
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

    public function test_validation_user_id_must_be_assigned(): void
    {
        $deadlineData = [
            'title' => 'Test Deadline',
            'deadline_at' => now()->addDays(7)->toDateTimeString(),
            'user_id' => $this->employeeNotAssigned->id, // Not assigned to this practice
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/practices/{$this->practice->id}/deadlines", $deadlineData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
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
