<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSyncApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_repeated_operation_creates_only_one_client(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $payload = [
            'first_name' => 'Giulia', 'last_name' => 'Rossi',
            'phone' => '3331234567', 'date_of_birth' => '1985-04-12',
        ];
        $headers = ['X-Operation-Id' => '4dd49cd4-91f3-4ec8-a3f5-e3e6e74c5a39'];

        $first = $this->actingAs($admin, 'api')->postJson('/api/v1/clients', $payload, $headers);
        $second = $this->actingAs($admin, 'api')->postJson('/api/v1/clients', $payload, $headers);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, ClientProfile::query()->count());
    }

    public function test_stale_client_version_returns_conflict(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();

        $this->actingAs($admin, 'api')->putJson('/api/v1/clients/'.$client->id, [
            'first_name' => 'Giulia', 'last_name' => 'Rossi',
            'phone' => '3331234567', 'date_of_birth' => '1985-04-12',
        ], [
            'X-Operation-Id' => 'f1ac94cb-3438-4f81-9697-876044c201bc',
            'If-Match' => '2000-01-01T00:00:00.000000Z',
        ])->assertStatus(409)->assertJsonPath('current.id', $client->id);
    }

    public function test_matching_client_version_allows_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();
        $version = $client->updated_at->toISOString();

        $this->actingAs($admin, 'api')->putJson('/api/v1/clients/'.$client->id, [
            'first_name' => 'Giulia', 'last_name' => 'Rossi',
            'phone' => '3331234567', 'date_of_birth' => '1985-04-12',
        ], [
            'X-Operation-Id' => '8d6ee711-04da-4da0-9a7d-c8c8efeb27fa',
            'If-Match' => $version,
        ])->assertOk()->assertJsonPath('data.first_name', 'Giulia');
    }

    public function test_change_feed_exposes_client_changes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();

        $this->actingAs($admin, 'api')->getJson('/api/v1/sync/changes?after=0')
            ->assertOk()->assertJsonFragment(['resource' => 'clients', 'resource_id' => $client->id]);
    }

    public function test_stale_appointment_version_returns_conflict(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $appointment = Appointment::factory()->create();

        $this->actingAs($admin, 'api')->putJson('/api/v1/appointments/'.$appointment->id, [], [
            'X-Operation-Id' => '39b9a004-c4da-47a3-9f96-a6c052c74249',
            'If-Match' => '2000-01-01T00:00:00.000000Z',
        ])->assertStatus(409)->assertJsonPath('current.id', $appointment->id);
    }

    public function test_deleted_client_from_another_branch_is_not_exposed_in_change_feed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $admin->branches()->attach($ownBranch);
        $client = ClientProfile::factory()->create(['branch_id' => $otherBranch->id]);
        $clientId = $client->id;
        $client->delete();

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/sync/changes?after=0')->assertOk();
        $this->assertNotContains($clientId, collect($response->json('data'))->where('resource', 'clients')->pluck('resource_id')->all());
    }
}
