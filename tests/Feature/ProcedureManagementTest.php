<?php

namespace Tests\Feature;

use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureManagementTest extends TestCase
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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    public function test_admin_can_view_procedures_index(): void
    {
        $procedure = Procedure::factory()->create(['name' => 'PROCEDURA_VISUALIZZABILE']);

        $this->actingAs($this->admin)
            ->get('/procedures')
            ->assertStatus(200);
    }

    public function test_admin_can_create_procedure(): void
    {
        $practiceType = PracticeType::factory()->create();

        $payload = [
            'procedure_type_id' => $practiceType->id,
            'name' => 'PROCEDURA_TEST_UNICA',
            'default_notes' => 'Nota default test',
        ];

        $this->actingAs($this->admin)
            ->post('/procedures', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('procedures', [
            'name' => 'PROCEDURA_TEST_UNICA',
            'procedure_type_id' => $practiceType->id,
        ]);
    }

    public function test_admin_can_update_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'name' => 'NOME_ORIGINALE',
        ]);

        $this->actingAs($this->admin)
            ->put('/procedures/'.$procedure->id, [
                'procedure_type_id' => $procedure->procedure_type_id,
                'name' => 'NOME_AGGIORNATO',
                'default_notes' => 'Nota aggiornata',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'name' => 'NOME_AGGIORNATO',
        ]);
    }

    public function test_admin_can_delete_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'name' => 'PROCEDURA_DA_ELIMINARE',
        ]);

        $this->actingAs($this->admin)
            ->delete('/procedures/'.$procedure->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('procedures', [
            'id' => $procedure->id,
        ]);
    }

    public function test_employee_cannot_create_procedure(): void
    {
        $practiceType = PracticeType::factory()->create();

        $this->actingAs($this->employee)
            ->post('/procedures', [
                'procedure_type_id' => $practiceType->id,
                'name' => 'PROCEDURA_NON_AUTORIZZATA',
                'default_notes' => 'Nota test',
            ])
            ->assertStatus(403);
    }

    public function test_employee_can_view_procedures(): void
    {
        Procedure::factory()->create(['name' => 'PROCEDURA_VISUALIZZABILE']);

        $this->actingAs($this->admin)
            ->get('/procedures')
            ->assertStatus(200);

        $this->actingAs($this->employee)
            ->get('/procedures')
            ->assertStatus(200);
    }

    public function test_duplicate_nome_within_same_practice_type_fails(): void
    {
        $practiceType = PracticeType::factory()->create();
        $procedure = Procedure::factory()->create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'PROCEDURA_DUPLICATA',
        ]);

        $this->actingAs($this->admin)
            ->post('/procedures', [
                'procedure_type_id' => $practiceType->id,
                'name' => 'PROCEDURA_DUPLICATA',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'name' => 'PROCEDURA_DUPLICATA',
        ]);
    }

    public function test_duplicate_nome_across_different_practice_types_succeeds(): void
    {
        $practiceType1 = PracticeType::factory()->create();
        $practiceType2 = PracticeType::factory()->create();
        Procedure::factory()->create([
            'procedure_type_id' => $practiceType1->id,
            'name' => 'PROCEDURA_STESSO_NOME',
        ]);

        $this->actingAs($this->admin)
            ->post('/procedures', [
                'procedure_type_id' => $practiceType2->id,
                'name' => 'PROCEDURA_STESSO_NOME',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('procedures', [
            'procedure_type_id' => $practiceType2->id,
            'name' => 'PROCEDURA_STESSO_NOME',
        ]);
    }
}
