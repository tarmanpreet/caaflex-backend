<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeDeadlineIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_view_all_deadlines_with_summary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();

        PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
            'deadline_at' => now()->subDay(),
        ]);
        PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_COMPLETED,
        ]);

        $this->actingAs($admin)
            ->get(route('deadlines.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Deadlines/Index')
                ->has('deadlines.data', 2)
                ->where('summary.total', 2)
                ->where('summary.open', 1)
                ->where('summary.overdue', 1)
                ->where('summary.completed', 1)
            );
    }

    public function test_employee_sees_only_deadlines_for_assigned_practices(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $assignedPractice = Practice::factory()->create();
        $assignedPractice->assignedUsers()->attach($employee->id, ['assigned_at' => now()]);
        $otherPractice = Practice::factory()->create();

        $visibleDeadline = PracticeDeadline::factory()->create(['practice_id' => $assignedPractice->id]);
        PracticeDeadline::factory()->create(['practice_id' => $otherPractice->id]);

        $this->actingAs($employee)
            ->get(route('deadlines.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('deadlines.data', 1)
                ->where('deadlines.data.0.id', $visibleDeadline->id)
                ->where('summary.total', 1)
            );
    }

    public function test_deadline_filters_do_not_change_summary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();
        $completed = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_COMPLETED,
        ]);
        PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('deadlines.index', ['status' => PracticeDeadline::STATUS_COMPLETED]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('deadlines.data', 1)
                ->where('deadlines.data.0.id', $completed->id)
                ->where('summary.total', 2)
            );
    }

    public function test_client_cannot_view_deadline_index(): void
    {
        $client = User::factory()->create();
        $client->assignRole('cliente');

        $this->actingAs($client)
            ->get(route('deadlines.index'))
            ->assertForbidden();
    }

    public function test_deadline_status_can_be_changed_from_web_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->put(route('practices.deadlines.update', [$practice, $deadline]), [
                'status' => PracticeDeadline::STATUS_IN_PROGRESS,
            ])
            ->assertRedirect();

        $this->assertSame(PracticeDeadline::STATUS_IN_PROGRESS, $deadline->fresh()->status);
    }
}
