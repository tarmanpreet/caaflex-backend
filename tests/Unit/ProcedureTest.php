<?php

namespace Tests\Unit;

use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Procedure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_procedure_with_valid_data(): void
    {
        $practiceType = PracticeType::create([
            'name' => 'Test Type',
            'duration_minutes' => 60,
            'color' => '#3B82F6',
        ]);

        $procedure = Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Test Procedure',
            'default_notes' => 'Default notes',
            
        ]);

        $this->assertDatabaseHas('procedures', [
            'name' => 'Test Procedure',
            'procedure_type_id' => $practiceType->id,
        ]);
    }

    public function test_cannot_create_procedure_without_nome(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $practiceType = PracticeType::create([
            'name' => 'Test Type',
            'duration_minutes' => 60,
            'color' => '#3B82F6',
        ]);

        // Test with null to trigger NOT NULL constraint
        Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => null,
        ]);
    }

    public function test_cannot_create_procedure_without_practice_type(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Procedure::create([
            'name' => 'Test Procedure',
        ]);
    }

    public function test_nome_is_unique_within_practice_type(): void
    {
        $practiceType = PracticeType::create([
            'name' => 'Test Type',
            'duration_minutes' => 60,
            'color' => '#3B82F6',
        ]);

        Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Duplicate Procedure',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Duplicate Procedure',
        ]);
    }

    public function test_procedure_belongs_to_practice_type(): void
    {
        $practiceType = PracticeType::create([
            'name' => 'Test Type',
            'duration_minutes' => 60,
            'color' => '#3B82F6',
        ]);

        $procedure = Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Test Procedure',
        ]);

        $this->assertInstanceOf(PracticeType::class, $procedure->practiceType);
        $this->assertEquals($practiceType->id, $procedure->practiceType->id);
    }

    public function test_practice_has_one_procedure(): void
    {
        $practiceType = PracticeType::create([
            'name' => 'Test Type',
            'duration_minutes' => 60,
            'color' => '#3B82F6',
        ]);

        $procedure = Procedure::create([
            'procedure_type_id' => $practiceType->id,
            'name' => 'Test Procedure',
        ]);

        $clientProfile = \App\Models\ClientProfile::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01',
        ]);

        $practice = Practice::create([
            'client_profile_id' => $clientProfile->id,
            'practice_type_id' => $practiceType->id,
            'procedure_id' => $procedure->id,
            'type' => '730',
            'status' => 'nuova',
        ]);

        $this->assertInstanceOf(Procedure::class, $practice->procedure);
        $this->assertEquals($procedure->id, $practice->procedure->id);
    }
} 
