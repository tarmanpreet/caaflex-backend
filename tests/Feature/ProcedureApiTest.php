<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\Procedure;
use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;
    protected User $unauthorizedUser;
    protected PracticeType $practiceType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Create admin user with full permissions
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        // Create employee user with limited permissions
        $this->employeeUser = User::factory()->create(['is_active' => true]);
        $this->employeeUser->assignRole('employee');

        // Create user without procedure permissions
        $this->unauthorizedUser = User::factory()->create(['is_active' => true]);
        $this->unauthorizedUser->assignRole('cliente');

        // Create a practice type for tests
        $this->practiceType = PracticeType::factory()->create([
            'name' => 'TestPracticeType_' . uniqid(),
            'duration_minutes' => 60,
        ]);
    }

    public function test_index_returns_paginated_procedures(): void
    {
        Procedure::factory()->count(5)->create([
            'procedure_type_id' => $this->practiceType->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/procedures');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'procedure_type_id',
                    'name',
                    'default_notes',
                    'practice_type',
                ],
            ])
            ->assertJsonCount(5);
    }

    public function test_index_returns_procedures_with_practice_type_relation(): void
    {
        Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Procedure With Type',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/procedures');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Procedure With Type'])
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'procedure_type_id',
                    'name',
                    'practice_type',
                ],
            ]);
    }

    public function test_store_creates_procedure(): void
    {
        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Test Procedure',
            'default_notes' => 'Default note for this procedure',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/procedures', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Procedure created.')
            ->assertJsonPath('data.name', 'Test Procedure')
            ->assertJsonPath('data.procedure_type_id', $this->practiceType->id);

        $this->assertDatabaseHas('procedures', [
            'name' => 'Test Procedure',
            'procedure_type_id' => $this->practiceType->id,
            'default_notes' => 'Default note for this procedure',
        ]);
    }

    public function test_store_validates_unique_nome_per_type(): void
    {
        Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Existing Procedure',
        ]);

        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Existing Procedure',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/procedures', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/procedures', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'procedure_type_id']);
    }

    public function test_store_validates_procedure_type_exists(): void
    {
        $payload = [
            'procedure_type_id' => 9999,
            'name' => 'Test Procedure',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/procedures', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['procedure_type_id']);
    }

    public function test_show_returns_procedure_with_relations(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Test Procedure',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/procedures/' . $procedure->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $procedure->id)
            ->assertJsonPath('data.name', 'Test Procedure')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'procedure_type_id',
                    'name',
                    'default_notes',
                    'practice_type',
                    'practices_count',
                ],
            ]);
    }

    public function test_update_modifies_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Original Name',
            'default_notes' => 'Original note',
        ]);

        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Updated Name',
            'default_notes' => 'Updated note',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->putJson('/api/v1/procedures/' . $procedure->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Procedure updated.')
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'name' => 'Updated Name',
            'default_notes' => 'Updated note',
        ]);
    }

    public function test_update_validates_unique_nome_per_type(): void
    {
        Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Existing Procedure',
        ]);

        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Another Procedure',
        ]);

        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Existing Procedure',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->putJson('/api/v1/procedures/' . $procedure->id, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_destroy_deletes_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Procedure to Delete',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson('/api/v1/procedures/' . $procedure->id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Procedure deleted.');

        $this->assertDatabaseMissing('procedures', [
            'id' => $procedure->id,
        ]);
    }

    public function test_destroy_returns_409_when_practices_attached(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Procedure with Practices',
        ]);

        // Create a practice attached to this procedure
        Practice::factory()->create([
            'procedure_id' => $procedure->id,
            'practice_type_id' => $this->practiceType->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->deleteJson('/api/v1/procedures/' . $procedure->id);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Cannot delete procedure with attached practices.');

        // Verify procedure still exists
        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/procedures');

        $response->assertStatus(401);
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $response = $this->actingAs($this->unauthorizedUser, 'api')
            ->getJson('/api/v1/procedures');

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_create(): void
    {
        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Unauthorized Procedure',
        ];

        $response = $this->actingAs($this->unauthorizedUser, 'api')
            ->postJson('/api/v1/procedures', $payload);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
        ]);

        $payload = [
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($this->unauthorizedUser, 'api')
            ->putJson('/api/v1/procedures/' . $procedure->id, $payload);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_delete(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
        ]);

        $response = $this->actingAs($this->unauthorizedUser, 'api')
            ->deleteJson('/api/v1/procedures/' . $procedure->id);

        $response->assertStatus(403);
    }

    public function test_employee_can_view_procedures(): void
    {
        Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Employee Viewable',
        ]);

        $response = $this->actingAs($this->employeeUser, 'api')
            ->getJson('/api/v1/procedures');

        $response->assertStatus(200);
    }

    public function test_same_nome_allowed_across_different_types(): void
    {
        $anotherPracticeType = PracticeType::factory()->create([
            'name' => 'AnotherType_' . uniqid(),
        ]);

        Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType->id,
            'name' => 'Same Name Procedure',
        ]);

        $payload = [
            'procedure_type_id' => $anotherPracticeType->id,
            'name' => 'Same Name Procedure',
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/procedures', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('procedures', [
            'procedure_type_id' => $anotherPracticeType->id,
            'name' => 'Same Name Procedure',
        ]);
    }
}