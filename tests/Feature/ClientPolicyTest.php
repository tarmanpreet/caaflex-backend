<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_employee_can_update_own_client(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        
        $client = ClientProfile::factory()->create([
            'created_by' => $employee->id,
        ]);
        
        $this->assertTrue(Gate::forUser($employee)->allows('update', $client));
    }

    public function test_employee_cannot_update_others_client(): void
    {
        $employee1 = User::factory()->create();
        $employee1->assignRole('employee');
        
        $employee2 = User::factory()->create();
        $employee2->assignRole('employee');
        
        $client = ClientProfile::factory()->create([
            'created_by' => $employee2->id,
        ]);
        
        $this->assertTrue(Gate::forUser($employee1)->denies('update', $client));
    }

    public function test_employee_cannot_delete_client(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        
        $client = ClientProfile::factory()->create([
            'created_by' => $employee->id,
        ]);
        
        $this->assertTrue(Gate::forUser($employee)->denies('delete', $client));
    }

    public function test_admin_can_delete_client(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $client = ClientProfile::factory()->create();
        
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $client));
    }

    public function test_superadmin_passes_all_policy_checks(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        
        $client = ClientProfile::factory()->create();
        
        $this->assertTrue(Gate::forUser($superadmin)->allows('viewAny', ClientProfile::class));
        $this->assertTrue(Gate::forUser($superadmin)->allows('view', $client));
        $this->assertTrue(Gate::forUser($superadmin)->allows('create', ClientProfile::class));
        $this->assertTrue(Gate::forUser($superadmin)->allows('update', $client));
        $this->assertTrue(Gate::forUser($superadmin)->allows('delete', $client));
        $this->assertTrue(Gate::forUser($superadmin)->allows('uploadDocument', $client));
        $this->assertTrue(Gate::forUser($superadmin)->allows('deleteDocument', $client));
    }
}
