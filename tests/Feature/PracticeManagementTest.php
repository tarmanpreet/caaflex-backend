<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
