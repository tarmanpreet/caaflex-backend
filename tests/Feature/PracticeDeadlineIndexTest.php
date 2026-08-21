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
        $assignee = User::factory()->create();
        $practice->assignedUsers()->attach($assignee->id, ['assigned_at' => now()]);

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
                ->where('deadlines.data.0.can_update', true)
                ->has('deadlines.data.0.practice.assigned_users', 1)
                ->missing('deadlines.data.0.practice.notes')
                ->missing('deadlines.data.0.practice.client.email')
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
                ->where('deadlines.data.0.can_update', true)
                ->where('summary.total', 1)
            );
    }

    public function test_deadline_update_action_is_hidden_when_policy_denies_update(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->givePermissionTo('practices.view-any');
        $practice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($employee)
            ->get(route('deadlines.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('deadlines.data', 1)
                ->where('deadlines.data.0.id', $deadline->id)
                ->where('deadlines.data.0.can_update', false)
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

    public function test_deadline_can_be_fully_updated_from_index_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();
        $assignee = User::factory()->create();
        $practice->assignedUsers()->attach($assignee->id, ['assigned_at' => now()]);
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);
        $newDeadlineAt = now()->addWeek()->startOfMinute();

        $this->actingAs($admin)
            ->put(route('practices.deadlines.update', [$practice, $deadline]), [
                'title' => 'Documenti dichiarazione aggiornati',
                'notes' => 'Verificare la documentazione ricevuta.',
                'deadline_at' => $newDeadlineAt->toDateTimeString(),
                'status' => PracticeDeadline::STATUS_IN_PROGRESS,
                'priority' => PracticeDeadline::PRIORITY_HIGH,
                'user_id' => $assignee->id,
            ])
            ->assertRedirect();

        $deadline->refresh();

        $this->assertSame('Documenti dichiarazione aggiornati', $deadline->title);
        $this->assertSame('Verificare la documentazione ricevuta.', $deadline->notes);
        $this->assertTrue($deadline->deadline_at->equalTo($newDeadlineAt));
        $this->assertSame(PracticeDeadline::STATUS_IN_PROGRESS, $deadline->status);
        $this->assertSame(PracticeDeadline::PRIORITY_HIGH, $deadline->priority);
        $this->assertSame($assignee->id, $deadline->user_id);
    }

    public function test_deadline_update_rejects_null_required_fields_and_keeps_record_unchanged(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'title' => 'Scadenza originale',
            'status' => PracticeDeadline::STATUS_PENDING,
            'priority' => PracticeDeadline::PRIORITY_MEDIUM,
        ]);
        $requiredFields = ['title', 'deadline_at', 'status', 'priority'];
        $originalAttributes = collect($requiredFields)
            ->mapWithKeys(fn (string $field): array => [$field => $deadline->getRawOriginal($field)])
            ->all();
        $validPayload = [
            'title' => $deadline->title,
            'deadline_at' => $deadline->deadline_at->toDateTimeString(),
            'status' => $deadline->status,
            'priority' => $deadline->priority,
        ];

        foreach ($requiredFields as $field) {
            $this->actingAs($admin)
                ->from(route('deadlines.index'))
                ->put(route('practices.deadlines.update', [$practice, $deadline]), [
                    ...$validPayload,
                    $field => null,
                ])
                ->assertRedirect(route('deadlines.index'))
                ->assertSessionHasErrors($field);

            $freshDeadline = $deadline->fresh();
            $freshAttributes = collect($requiredFields)
                ->mapWithKeys(fn (string $requiredField): array => [$requiredField => $freshDeadline->getRawOriginal($requiredField)])
                ->all();

            $this->assertSame($originalAttributes, $freshAttributes);
        }
    }

    public function test_deadline_update_returns_not_found_for_a_different_practice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $practice = Practice::factory()->create();
        $otherPractice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $otherPractice->id]);

        $this->actingAs($admin)
            ->put(route('practices.deadlines.update', [$practice, $deadline]), [
                'title' => 'Tentativo non valido',
            ])
            ->assertNotFound();

        $this->assertNotSame('Tentativo non valido', $deadline->fresh()->title);
    }

    public function test_deadline_update_is_forbidden_when_policy_denies_it(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $employee->givePermissionTo('practices.view-any');
        $practice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($employee)
            ->put(route('practices.deadlines.update', [$practice, $deadline]), [
                'title' => 'Tentativo non autorizzato',
            ])
            ->assertForbidden();

        $this->assertNotSame('Tentativo non autorizzato', $deadline->fresh()->title);
    }
}
