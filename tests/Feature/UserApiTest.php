<?php

namespace Tests\Feature;

use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['is_active' => true]);
        $this->employee->assignRole('employee');
    }

    public function test_admin_can_create_user(): void
    {
        $practiceTypeA = PracticeType::factory()->create(['name' => '730']);
        $practiceTypeB = PracticeType::factory()->create(['name' => 'ISEE']);

        $payload = [
            'name' => 'Mario Rossi',
            'email' => 'mario.rossi@email.it',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'is_active' => true,
            'practice_type_ids' => [$practiceTypeA->id, $practiceTypeB->id],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'User created successfully.')
            ->assertJsonPath('data.name', 'Mario Rossi')
            ->assertJsonPath('data.email', 'mario.rossi@email.it')
            ->assertJsonPath('data.role_name', 'employee')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(2, 'data.practice_types');

        $this->assertDatabaseHas('users', [
            'email' => 'mario.rossi@email.it',
            'name' => 'Mario Rossi',
            'is_active' => true,
        ]);
    }

    public function test_employee_cannot_create_user(): void
    {
        $response = $this->actingAs($this->employee, 'api')
            ->postJson('/api/v1/users', [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_user_validates_payload(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/users', [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
                'role' => 'unknown-role',
                'practice_type_ids' => ['bad'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'role',
                'practice_type_ids.0',
            ]);
    }
}
