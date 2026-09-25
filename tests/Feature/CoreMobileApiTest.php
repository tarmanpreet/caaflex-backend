<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\Procedure;
use App\Models\ProcedureDeadlineTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreMobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_public_app_config_exposes_only_mobile_safe_configuration(): void
    {
        config([
            'branding.name' => 'CAFlex',
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'public-key',
            'broadcasting.connections.reverb.secret' => 'do-not-expose',
        ]);

        $this->getJson('/api/v1/app-config')
            ->assertOk()
            ->assertJsonPath('data.branding.name', 'CAFlex')
            ->assertJsonPath('data.branding.logo_light_url', '/brand/caaflex-logo-light.png')
            ->assertJsonPath('data.realtime.enabled', true)
            ->assertJsonPath('data.realtime.key', 'public-key')
            ->assertJsonPath('data.features.deadlines', true)
            ->assertJsonStructure(['data' => ['minimum_versions' => ['ios', 'android']]])
            ->assertJsonMissingPath('data.realtime.secret');
    }

    public function test_me_is_a_complete_mobile_bootstrap(): void
    {
        $branch = Branch::factory()->create(['name' => 'Roma Centro']);
        $this->admin->branches()->attach($branch, ['assigned_at' => now()]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $this->admin->id)
            ->assertJsonPath('roles.0', 'admin')
            ->assertJsonPath('capabilities.practices.view-any', true)
            ->assertJsonPath('capabilities.admins.create', false)
            ->assertJsonPath('branches.0.id', $branch->id)
            ->assertJsonPath('branding.name', config('branding.name'))
            ->assertJsonPath('features.dashboard', true)
            ->assertJsonMissingPath('realtime.secret');
    }

    public function test_dashboard_api_uses_the_shared_dashboard_payload(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats',
                    'deadlines',
                    'activities',
                    'practices',
                    'efficiency' => ['value', 'caption', 'completed', 'total'],
                ],
            ]);
    }

    public function test_practice_detail_contains_mobile_parity_relations(): void
    {
        $branch = Branch::factory()->create();
        $procedure = Procedure::factory()->create();
        ProcedureDeadlineTemplate::factory()->create(['procedure_id' => $procedure->id]);
        $practice = Practice::factory()->create([
            'branch_id' => $branch->id,
            'procedure_id' => $procedure->id,
        ]);
        PracticeDeadline::factory()->create(['practice_id' => $practice->id]);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/practices/{$practice->id}")
            ->assertOk()
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.procedure.id', $procedure->id)
            ->assertJsonCount(1, 'data.procedure.deadline_templates')
            ->assertJsonCount(1, 'data.deadlines')
            ->assertJsonStructure([
                'data' => [
                    'tracking_code',
                    'branch',
                    'procedure' => ['deadline_templates'],
                    'deadlines' => [['assignee', 'reminders', 'steps']],
                ],
            ]);
    }

    public function test_client_detail_includes_searchable_paginated_practices(): void
    {
        $client = ClientProfile::factory()->create();
        $matching = Practice::factory()->create(['client_profile_id' => $client->id, 'type' => 'ISEE']);
        Practice::factory()->create(['client_profile_id' => $client->id, 'type' => '730']);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/clients/{$client->id}?practice_search=ISEE")
            ->assertOk()
            ->assertJsonPath('filters.practice_search', 'ISEE')
            ->assertJsonPath('practices.total', 1)
            ->assertJsonPath('practices.data.0.id', $matching->id);
    }

    public function test_global_deadline_index_and_explicit_complete_endpoint_are_available(): void
    {
        $practice = Practice::factory()->create();
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => $practice->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/deadlines?timing=open')
            ->assertOk()
            ->assertJsonPath('summary.open', 1)
            ->assertJsonPath('data.data.0.id', $deadline->id);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/practices/{$practice->id}/deadlines/{$deadline->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', PracticeDeadline::STATUS_COMPLETED);

        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $deadline->fresh()->status);
    }

    public function test_public_practice_status_returns_only_code_and_status(): void
    {
        $practice = Practice::factory()->create(['status' => 'in_lavorazione']);

        $this->postJson('/api/v1/practice-status', ['code' => strtolower($practice->tracking_code)])
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'code' => $practice->tracking_code,
                    'status' => 'in_lavorazione',
                ],
            ]);

        $this->postJson('/api/v1/practice-status', ['code' => 'AAAAAAAAAA'])
            ->assertNotFound()
            ->assertJsonPath('data', null);
    }

    public function test_user_api_returns_and_updates_branch_assignments(): void
    {
        $branch = Branch::factory()->create();
        $replacementBranch = Branch::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/users', [
                'name' => 'Operatore Mobile',
                'email' => 'operatore.mobile@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
                'branch_ids' => [$branch->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.branches.0.id', $branch->id);

        $userId = $response->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/users/{$userId}")
            ->assertOk()
            ->assertJsonPath('data.user.branches.0.id', $branch->id)
            ->assertJsonFragment([
                'id' => $branch->id,
                'name' => $branch->name,
            ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/users/{$userId}", [
                'name' => 'Operatore Mobile',
                'email' => 'operatore.mobile@example.test',
                'role' => 'employee',
                'branch_ids' => [$replacementBranch->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.branches.0.id', $replacementBranch->id);
    }
}
