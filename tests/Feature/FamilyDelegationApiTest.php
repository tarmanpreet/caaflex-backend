<?php

namespace Tests\Feature;

use App\Models\ClientDelegation;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FamilyDelegationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $delegate;

    private User $owner;

    private ClientProfile $profile;

    private ClientDelegation $delegation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        Storage::fake('local');
        $this->owner = User::factory()->create(['is_active' => true]);
        $this->owner->assignRole('cliente');
        $this->delegate = User::factory()->create(['is_active' => true]);
        $this->delegate->assignRole('cliente');
        $this->profile = ClientProfile::factory()->forUser($this->owner)->create(['fiscal_code' => 'RSSMRC80A01H501Z']);
        $this->delegation = ClientDelegation::factory()->create(['client_profile_id' => $this->profile->id, 'delegate_user_id' => $this->delegate->id, 'scopes' => ['view'], 'accepted_at' => now()]);
    }

    public function test_profile_document_download_requires_scope_and_records_access(): void
    {
        $doc = ClientDocument::factory()->create(['client_profile_id' => $this->profile->id, 'disk_path' => 'qa/test.pdf']);
        Storage::disk('local')->put($doc->disk_path, '%PDF test');
        $url = '/api/v1/family/clients/'.$this->profile->id.'/documents/'.$doc->id.'/download';
        $this->actingAs($this->delegate, 'api')->get($url)->assertForbidden();
        $this->delegation->update(['scopes' => ['view', 'documents']]);
        $this->get($url)->assertOk();
        $this->assertDatabaseHas('activity_entries', ['user_id' => $this->delegate->id, 'client_profile_id' => $this->profile->id, 'action' => 'document.downloaded']);
        $other = ClientDocument::factory()->create();
        $this->get('/api/v1/family/clients/'.$this->profile->id.'/documents/'.$other->id.'/download')->assertForbidden();
        $this->delegation->update(['revoked_at' => now()]);
        $this->get($url)->assertForbidden();
    }

    public function test_search_of_delegated_profiles_does_not_disclose_fiscal_codes(): void
    {
        Practice::factory()->create(['client_profile_id' => $this->profile->id, 'type' => '730']);
        $this->actingAs($this->delegate, 'api')->getJson('/api/v1/operations/search?q=RSSMRC80A01H501Z')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/operations/search?q='.urlencode($this->profile->last_name))->assertOk()->assertDontSee('RSSMRC80A01H501Z');
    }

    public function test_delegation_cannot_be_revoked_through_an_unrelated_profile(): void
    {
        $other = ClientProfile::factory()->forUser($this->delegate)->create();
        $this->actingAs($this->delegate, 'api')->deleteJson('/api/v1/family/clients/'.$other->id.'/delegations/'.$this->delegation->id)->assertNotFound();
        $this->assertNull($this->delegation->fresh()->revoked_at);
    }

    public function test_staff_family_profile_only_lists_assigned_practices_and_their_activity(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');
        $this->profile->update(['created_by' => $employee->id]);
        $visible = Practice::factory()->create(['client_profile_id' => $this->profile->id]);
        $visible->assignedUsers()->attach($employee->id);
        $hidden = Practice::factory()->create(['client_profile_id' => $this->profile->id]);
        \App\Models\ActivityEntry::factory()->create(['client_profile_id' => $this->profile->id, 'practice_id' => $visible->id, 'action' => 'practice.updated']);
        \App\Models\ActivityEntry::factory()->create(['client_profile_id' => $this->profile->id, 'practice_id' => $hidden->id, 'action' => 'practice.updated']);
        $this->actingAs($employee, 'api')->getJson('/api/v1/family/clients/'.$this->profile->id)->assertOk()->assertJsonCount(1, 'data.practices.data')->assertJsonPath('data.practices.data.0.id', $visible->id)->assertJsonCount(1, 'data.activity.data')->assertJsonPath('data.activity.data.0.practice_id', $visible->id);
    }
}
