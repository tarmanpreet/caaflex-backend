<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeProcedureIdTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected PracticeType $practiceType730;

    protected PracticeType $practiceTypeIsee;

    protected Procedure $procedure;

    protected ClientProfile $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->practiceType730 = PracticeType::factory()->create(['name' => '730']);
        $this->practiceTypeIsee = PracticeType::factory()->create(['name' => 'ISEE']);

        $this->procedure = Procedure::factory()->create([
            'procedure_type_id' => $this->practiceType730->id,
            'name' => 'Test Procedure',
        ]);

        $this->client = ClientProfile::factory()->create();
    }

    public function test_procedure_id_auto_derives_practice_type_id_on_store(): void
    {
        $payload = [
            'client_profile_id' => $this->client->id,
            'type' => '730',
            'procedure_id' => $this->procedure->id,
            // practice_type_id intentionally omitted - should be auto-derived
        ];

        $response = $this->actingAs($this->admin)
            ->post('/practices', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $this->client->id,
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceType730->id,
        ]);
    }

    public function test_procedure_id_with_matching_practice_type_id_succeeds(): void
    {
        $payload = [
            'client_profile_id' => $this->client->id,
            'type' => '730',
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceType730->id, // Matches procedure's type
        ];

        $response = $this->actingAs($this->admin)
            ->post('/practices', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $this->client->id,
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceType730->id,
        ]);
    }

    public function test_procedure_id_with_mismatching_practice_type_id_fails(): void
    {
        $payload = [
            'client_profile_id' => $this->client->id,
            'type' => '730',
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceTypeIsee->id, // Mismatch!
        ];

        $response = $this->actingAs($this->admin)
            ->post('/practices', $payload);

        $response->assertRedirect()
            ->assertSessionHasErrors(['practice_type_id']);

        $this->assertDatabaseMissing('practices', [
            'client_profile_id' => $this->client->id,
            'procedure_id' => $this->procedure->id,
        ]);
    }

    public function test_procedure_id_auto_derives_on_update(): void
    {
        // Create practice without procedure
        $practice = Practice::factory()->create([
            'client_profile_id' => $this->client->id,
            'type' => '730',
            'procedure_id' => null,
            'practice_type_id' => null,
        ]);

        $payload = [
            'procedure_id' => $this->procedure->id,
            // practice_type_id intentionally omitted - should be auto-derived
        ];

        $response = $this->actingAs($this->admin)
            ->put('/practices/'.$practice->id, $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('practices', [
            'id' => $practice->id,
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceType730->id,
        ]);
    }

    public function test_update_with_procedure_type_mismatch_fails(): void
    {
        // Create practice with procedure
        $practice = Practice::factory()->create([
            'client_profile_id' => $this->client->id,
            'type' => '730',
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceType730->id,
        ]);

        $payload = [
            'procedure_id' => $this->procedure->id,
            'practice_type_id' => $this->practiceTypeIsee->id, // Mismatch!
        ];

        $response = $this->actingAs($this->admin)
            ->put('/practices/'.$practice->id, $payload);

        $response->assertRedirect()
            ->assertSessionHasErrors(['practice_type_id']);

        // Verify practice was not updated
        $this->assertDatabaseHas('practices', [
            'id' => $practice->id,
            'practice_type_id' => $this->practiceType730->id,
        ]);
    }
}
