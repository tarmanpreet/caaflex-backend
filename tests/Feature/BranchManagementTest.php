<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_see_branches_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('branches.index'));

        $response->assertStatus(200);
    }

    public function test_employee_cannot_see_branches_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('branches.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_branch(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $data = [
            'name' => 'Sede Milano',
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'postal_code' => '20100',
            'phone' => '+39 02 1234567',
            'vat_number' => '12345678901',
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post(route('branches.store'), $data);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', ['name' => 'Sede Milano']);
    }

    public function test_admin_can_update_branch(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $branch = Branch::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'address' => $branch->address,
            'city' => $branch->city,
            'province' => $branch->province,
            'postal_code' => $branch->postal_code,
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->put(route('branches.update', $branch), $data);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Updated Name']);
    }

    public function test_cannot_delete_last_branch(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $branch = Branch::factory()->create();

        $response = $this->actingAs($user)->delete(route('branches.destroy', $branch));

        $response->assertForbidden();
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_can_delete_branch_when_multiple_exist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        $response = $this->actingAs($user)->delete(route('branches.destroy', $branch1));

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseMissing('branches', ['id' => $branch1->id]);
        $this->assertDatabaseHas('branches', ['id' => $branch2->id]);
    }

    public function test_branch_has_employees_relationship(): void
    {
        $branch = Branch::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $branch->employees()->attach($user1);
        $branch->employees()->attach($user2);

        $this->assertEquals(2, $branch->employees()->count());
    }

    public function test_user_has_branches_relationship(): void
    {
        $user = User::factory()->create();
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        $user->branches()->attach($branch1);
        $user->branches()->attach($branch2);

        $this->assertEquals(2, $user->branches()->count());
    }

    public function test_employee_sees_only_assigned_branch_practices(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        $employee->branches()->attach($branch1);

        $practice1 = \App\Models\Practice::factory()->create(['branch_id' => $branch1->id]);
        $practice2 = \App\Models\Practice::factory()->create(['branch_id' => $branch2->id]);
        $practice3 = \App\Models\Practice::factory()->create(['branch_id' => null]);

        $practice1->assignedUsers()->attach($employee);
        $practice2->assignedUsers()->attach($employee);
        $practice3->assignedUsers()->attach($employee);

        $this->actingAs($employee)
            ->get(route('practices.index'))
            ->assertInertia(fn ($page) => $page
                ->where('practices.total', 2)
                ->has('practices.data', 2)
            );
    }

    public function test_branch_full_address(): void
    {
        $branch = Branch::factory()->create([
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'postal_code' => '20100',
        ]);

        $this->assertEquals('Via Roma 1, 20100 Milano (MI)', $branch->fullAddress());
    }

    public function test_update_appointment_action_preserves_null_branch_id(): void
    {
        $action = new \App\Actions\Appointment\UpdateAppointmentAction(
            new \App\Actions\Appointment\ConfirmAppointmentAction
        );

        $client = \App\Models\ClientProfile::factory()->create();
        $practiceType = \App\Models\PracticeType::factory()->create();
        $branch = Branch::factory()->create();

        $appointment = \App\Models\Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'branch_id' => $branch->id,
        ]);

        $action->execute([], $appointment, 1, ['branch_id' => null]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'branch_id' => null,
        ]);
    }

    public function test_update_practice_action_preserves_null_branch_id(): void
    {
        $action = new \App\Actions\Practice\UpdatePracticeAction;

        $client = \App\Models\ClientProfile::factory()->create();
        $branch = Branch::factory()->create();

        $practice = \App\Models\Practice::factory()->create([
            'client_profile_id' => $client->id,
            'branch_id' => $branch->id,
        ]);

        $action->execute([], $practice, 1, ['branch_id' => null]);

        $this->assertDatabaseHas('practices', [
            'id' => $practice->id,
            'branch_id' => null,
        ]);
    }

    public function test_update_appointment_action_changes_branch_id(): void
    {
        $action = new \App\Actions\Appointment\UpdateAppointmentAction(
            new \App\Actions\Appointment\ConfirmAppointmentAction
        );

        $client = \App\Models\ClientProfile::factory()->create();
        $practiceType = \App\Models\PracticeType::factory()->create();
        $branch2 = Branch::factory()->create();

        $appointment = \App\Models\Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'branch_id' => null,
        ]);

        $action->execute([], $appointment, 1, ['branch_id' => $branch2->id]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'branch_id' => $branch2->id,
        ]);
    }

    public function test_update_practice_action_changes_branch_id(): void
    {
        $action = new \App\Actions\Practice\UpdatePracticeAction;

        $client = \App\Models\ClientProfile::factory()->create();
        $branch2 = Branch::factory()->create();

        $practice = \App\Models\Practice::factory()->create([
            'client_profile_id' => $client->id,
            'branch_id' => null,
        ]);

        $action->execute([], $practice, 1, ['branch_id' => $branch2->id]);

        $this->assertDatabaseHas('practices', [
            'id' => $practice->id,
            'branch_id' => $branch2->id,
        ]);
    }
}
