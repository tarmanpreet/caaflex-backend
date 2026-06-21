<?php

namespace Tests\Unit;

use App\Models\Practice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PracticePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    public function test_admin_can_view_any_practices(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Practice::class));
    }

    public function test_employee_cannot_view_any_practices(): void
    {
        $this->assertTrue(Gate::forUser($this->employee)->denies('viewAny', Practice::class));
    }

    public function test_admin_can_view_any_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $practice));
    }

    public function test_employee_can_view_assigned_practice(): void
    {
        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($this->employee->id);

        $this->assertTrue(Gate::forUser($this->employee)->allows('view', $practice));
    }

    public function test_employee_cannot_view_unassigned_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->denies('view', $practice));
    }

    public function test_admin_can_create_practice(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Practice::class));
    }

    public function test_employee_can_create_practice(): void
    {
        $this->assertTrue(Gate::forUser($this->employee)->allows('create', Practice::class));
    }

    public function test_admin_can_update_any_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $practice));
    }

    public function test_employee_can_update_assigned_practice(): void
    {
        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($this->employee->id);

        $this->assertTrue(Gate::forUser($this->employee)->allows('update', $practice));
    }

    public function test_employee_cannot_update_unassigned_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->denies('update', $practice));
    }

    public function test_admin_can_delete_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $practice));
    }

    public function test_employee_cannot_delete_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->denies('delete', $practice));
    }

    public function test_admin_can_assign_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('assign', $practice));
    }

    public function test_employee_cannot_assign_practice(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->denies('assign', $practice));
    }

    public function test_admin_can_upload_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('uploadDocument', $practice));
    }

    public function test_employee_can_upload_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->allows('uploadDocument', $practice));
    }

    public function test_admin_can_download_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('downloadDocument', $practice));
    }

    public function test_employee_can_download_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->allows('downloadDocument', $practice));
    }

    public function test_admin_can_delete_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('deleteDocument', $practice));
    }

    public function test_employee_cannot_delete_document(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->denies('deleteDocument', $practice));
    }

    public function test_admin_can_create_note(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('createNote', $practice));
    }

    public function test_employee_can_create_note(): void
    {
        $practice = Practice::factory()->create();

        $this->assertTrue(Gate::forUser($this->employee)->allows('createNote', $practice));
    }
}
