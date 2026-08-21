<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    public function test_admin_can_delete_employee(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->employee))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Utente eliminato.');

        $this->assertModelMissing($this->employee);
    }

    public function test_admin_can_delete_employee_through_api(): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/users/'.$this->employee->id)
            ->assertOk()
            ->assertJsonPath('message', 'Utente eliminato.');

        $this->assertModelMissing($this->employee);
    }

    public function test_admin_cannot_delete_self_or_another_admin(): void
    {
        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole('admin');

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->admin))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $anotherAdmin))
            ->assertForbidden();

        $this->assertModelExists($this->admin);
        $this->assertModelExists($anotherAdmin);
    }

    public function test_superadmin_can_delete_admin_but_cannot_delete_superadmin(): void
    {
        $anotherSuperadmin = User::factory()->create();
        $anotherSuperadmin->assignRole('superadmin');

        $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $this->admin))
            ->assertRedirect(route('users.index'));

        $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $anotherSuperadmin))
            ->assertForbidden();

        $this->assertModelMissing($this->admin);
        $this->assertModelExists($anotherSuperadmin);
    }

    public function test_employee_cannot_delete_users(): void
    {
        $anotherEmployee = User::factory()->create();
        $anotherEmployee->assignRole('employee');

        $this->actingAs($this->employee)
            ->delete(route('users.destroy', $anotherEmployee))
            ->assertForbidden();

        $this->assertModelExists($anotherEmployee);
    }

    public function test_admin_cannot_promote_employee_to_superadmin_or_update_admin(): void
    {
        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole('admin');

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->employee), [
                'name' => $this->employee->name,
                'email' => $this->employee->email,
                'role' => 'superadmin',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($this->admin)
            ->put(route('users.update', $anotherAdmin), [
                'name' => $anotherAdmin->name,
                'email' => $anotherAdmin->email,
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->assertTrue($this->employee->fresh()->hasRole('employee'));
        $this->assertTrue($anotherAdmin->fresh()->hasRole('admin'));
    }
}
