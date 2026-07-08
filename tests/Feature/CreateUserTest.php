<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
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

        $this->superadmin = User::factory()->create(['is_active' => true]);
        $this->superadmin->assignRole('superadmin');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['is_active' => true]);
        $this->employee->assignRole('employee');
    }

    public function test_superadmin_can_create_admin(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('users.store'), [
                'name' => 'Admin Due',
                'email' => 'admin.due@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('users.show', User::where('email', 'admin.due@example.com')->first()));

        $user = User::where('email', 'admin.due@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_superadmin_can_create_employee_with_practice_types_and_branches(): void
    {
        $practiceType = PracticeType::factory()->create();
        $branch = Branch::factory()->create(['is_active' => true]);

        $this->actingAs($this->superadmin)
            ->post(route('users.store'), [
                'name' => 'Employee Nuovo',
                'email' => 'employee.nuovo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
                'practice_type_ids' => [$practiceType->id],
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect(route('users.show', User::where('email', 'employee.nuovo@example.com')->first()));

        $user = User::where('email', 'employee.nuovo@example.com')->first();
        $this->assertTrue($user->hasRole('employee'));
        $this->assertDatabaseHas('practice_type_user', ['user_id' => $user->id, 'practice_type_id' => $practiceType->id]);
        $this->assertDatabaseHas('branch_user', ['user_id' => $user->id, 'branch_id' => $branch->id]);
    }

    public function test_admin_can_create_employee(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Employee Tre',
                'email' => 'employee.tre@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
            ])
            ->assertRedirect(route('users.show', User::where('email', 'employee.tre@example.com')->first()));

        $this->assertTrue(User::where('email', 'employee.tre@example.com')->first()->hasRole('employee'));
    }

    public function test_admin_cannot_create_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Admin Bloccato',
                'email' => 'admin.bloccato@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'admin.bloccato@example.com']);
    }

    public function test_employee_cannot_create_user(): void
    {
        $this->actingAs($this->employee)
            ->post(route('users.store'), [
                'name' => 'Blocked',
                'email' => 'blocked@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_create_page_shows_assignable_roles_per_actor(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('users.create'))
            ->assertInertia(fn ($page) => $page
                ->component('Users/Create')
                ->where('assignableRoles', ['employee', 'admin'])
            );

        $this->actingAs($this->admin)
            ->get(route('users.create'))
            ->assertInertia(fn ($page) => $page
                ->component('Users/Create')
                ->where('assignableRoles', ['employee'])
            );
    }

    public function test_employee_cannot_access_create_page(): void
    {
        $this->actingAs($this->employee)
            ->get(route('users.create'))
            ->assertStatus(403);
    }

    public function test_users_index_sorts_by_name_desc_backend(): void
    {
        User::factory()->create(['name' => 'Zeno User']);
        User::factory()->create(['name' => 'Anna User']);
        User::factory()->create(['name' => 'Marco User']);

        $response = $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'desc')
            );

        $names = $response->viewData('page')['props']['users']['data'];
        $nameList = array_map(fn ($u) => $u['name'], $names);
        $sorted = $nameList;
        rsort($sorted);
        $this->assertSame($sorted, $nameList, 'Users are not sorted by name desc.');
    }

    public function test_users_index_sorts_by_email_asc_backend(): void
    {
        User::factory()->create(['email' => 'zzz@example.com']);
        User::factory()->create(['email' => 'aaa@example.com']);
        User::factory()->create(['email' => 'mmm@example.com']);

        $response = $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'email', 'direction' => 'asc']))
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort', 'email')
                ->where('filters.direction', 'asc')
            );

        $emails = $response->viewData('page')['props']['users']['data'];
        $emailList = array_map(fn ($u) => $u['email'], $emails);
        $sorted = $emailList;
        sort($sorted);
        $this->assertSame($sorted, $emailList, 'Users are not sorted by email asc.');
    }

    public function test_users_index_falls_back_to_name_for_unknown_sort_column(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'password', 'direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'desc')
            );
    }

    public function test_users_index_normalizes_invalid_direction_to_asc(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'name', 'direction' => 'invalid']))
            ->assertInertia(fn ($page) => $page
                ->where('filters.direction', 'asc')
            );
    }

    public function test_users_index_defaults_to_name_asc(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'asc')
            );
    }
}
