<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_superadmin_passes_all_gates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $this->assertTrue($user->can('admins.create'));
        $this->assertTrue($user->can('admins.delete'));
        $this->assertTrue($user->can('clients.delete'));
        $this->assertTrue($user->can('users.delete'));
    }

    public function test_admin_cannot_create_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertFalse($user->can('admins.create'));
        $this->assertFalse($user->can('admins.delete'));
    }

    public function test_employee_cannot_delete_client(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $this->assertFalse($user->can('clients.delete'));
        $this->assertFalse($user->can('documents.delete'));
        $this->assertFalse($user->can('users.view-any'));
    }

    public function test_inertia_shares_user_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->has('auth.user.roles')
                ->has('auth.user.permissions')
            );
    }
}
