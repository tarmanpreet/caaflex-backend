<?php

namespace Tests\Feature;

use App\Models\ActivityEntry;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PracticeOperationsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Practice $practice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->client = User::factory()->create(['is_active' => true]);
        $this->client->assignRole('cliente');
        $profile = ClientProfile::factory()->forUser($this->client)->create();
        $this->practice = Practice::factory()->create(['client_profile_id' => $profile->id, 'reference_year' => now()->year, 'status' => 'completata']);
        Notification::fake();
    }

    public function test_renewal_copies_required_data_without_documents_and_is_unique_across_the_chain(): void
    {
        PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id, 'status' => 'approved']);
        PracticeDocument::factory()->create(['practice_id' => $this->practice->id]);
        $url = '/api/v1/practices/'.$this->practice->id.'/workspace/renew';
        $response = $this->actingAs($this->admin, 'api')->postJson($url, ['reference_year' => now()->year + 1])->assertCreated();
        $id = $response->json('data.practice.id');
        $renewal = Practice::findOrFail($id);
        $this->assertSame('nuova', $renewal->status);
        $this->assertNotSame($this->practice->tracking_code, $renewal->tracking_code);
        $this->assertSame($this->practice->id, $renewal->renewal_of_id);
        $this->assertSame(0, $renewal->documents()->count());
        $this->assertDatabaseHas('practice_document_requests', ['practice_id' => $id, 'status' => 'requested']);
        $this->postJson($url, ['reference_year' => now()->year + 1])->assertUnprocessable();
        $this->postJson('/api/v1/practices/'.$id.'/workspace/renew', ['reference_year' => now()->year + 2])->assertCreated();
        $this->postJson($url, ['reference_year' => now()->year + 2])->assertUnprocessable();
    }

    public function test_customer_cannot_renew_or_run_staff_bulk_actions(): void
    {
        $this->actingAs($this->client, 'api')->postJson('/api/v1/practices/'.$this->practice->id.'/workspace/renew', ['reference_year' => now()->year + 1])->assertForbidden();
        $this->postJson('/api/v1/operations/bulk', ['ids' => [$this->practice->id], 'action' => 'status', 'status' => 'nuova'])->assertForbidden();
        $this->getJson('/api/v1/operations')->assertForbidden();
    }

    public function test_bulk_is_atomic_when_one_record_is_not_authorized(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');
        $other = Practice::factory()->create(['status' => 'nuova']);
        $this->practice->assignedUsers()->attach($employee->id);
        $this->actingAs($employee, 'api')->postJson('/api/v1/operations/bulk', ['ids' => [$this->practice->id, $other->id], 'action' => 'status', 'status' => 'sospesa'])->assertForbidden();
        $this->assertSame('completata', $this->practice->fresh()->status);
        $this->assertSame('nuova', $other->fresh()->status);
    }

    public function test_admin_bulk_status_and_assignment_update_records_and_history(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/operations/bulk', ['ids' => [$this->practice->id], 'action' => 'status', 'status' => 'sospesa'])->assertOk()->assertJsonPath('data.count', 1);
        $this->assertDatabaseHas('practice_status_logs', ['practice_id' => $this->practice->id, 'new_status' => 'sospesa']);
        $this->postJson('/api/v1/operations/bulk', ['ids' => [$this->practice->id], 'action' => 'assign', 'user_ids' => [$this->admin->id]])->assertOk();
        $this->assertTrue($this->practice->assignedUsers()->where('users.id', $this->admin->id)->exists());
        $this->assertDatabaseHas('activity_entries', ['practice_id' => $this->practice->id, 'action' => 'practice.bulk_assign']);
    }

    public function test_global_search_does_not_expose_other_clients_or_internal_files(): void
    {
        $request = PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id]);
        $public = PracticeDocument::factory()->create(['practice_id' => $this->practice->id, 'document_request_id' => $request->id, 'original_name' => 'unique-pubblico.pdf']);
        PracticeDocument::factory()->create(['practice_id' => $this->practice->id, 'original_name' => 'unique-interno.pdf']);
        PracticeDocument::factory()->create(['original_name' => 'unique-altro-cliente.pdf']);
        $this->actingAs($this->client, 'api')->getJson('/api/v1/operations/search?q=unique')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->id)->assertDontSee('disk_path')->assertDontSee('unique-interno.pdf');
        $this->getJson('/api/v1/operations/search?q=u')->assertUnprocessable();
    }

    public function test_workload_counts_only_accessible_branches(): void
    {
        $branch = Branch::factory()->create();
        $other = Branch::factory()->create();
        $this->admin->branches()->attach($branch->id);
        $this->practice->update(['branch_id' => $branch->id, 'status' => 'in_lavorazione', 'deadline_at' => now()->subDay()]);
        $this->practice->assignedUsers()->attach($this->admin->id);
        $hidden = Practice::factory()->create(['branch_id' => $other->id, 'status' => 'in_lavorazione']);
        $hidden->assignedUsers()->attach($this->admin->id);
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/operations')->assertOk();
        $row = collect($response->json('data.workload'))->firstWhere('id', $this->admin->id);
        $this->assertSame(1, $row['open_count']);
        $this->assertSame(1, $row['overdue_count']);
        $this->getJson('/api/v1/operations?branch_id='.$other->id)->assertForbidden();
    }

    public function test_general_audit_records_field_names_without_sensitive_values(): void
    {
        $this->actingAs($this->admin, 'api');
        $this->practice->update(['notes' => 'private content never copied into audit']);
        $entry = ActivityEntry::query()->where('practice_id', $this->practice->id)->where('action', 'practice.updated')->latest('id')->firstOrFail();
        $this->assertContains('notes', $entry->metadata['fields']);
        $this->assertStringNotContainsString('private content', json_encode($entry->metadata));
    }

    public function test_staff_download_permission_does_not_override_delegation_limits(): void
    {
        $local = Branch::factory()->create();
        $remote = Branch::factory()->create();
        $this->client->assignRole('employee');
        $this->client->branches()->attach($local->id);
        $other = ClientProfile::factory()->create(['branch_id' => $remote->id, 'fiscal_code' => 'RSSMRC80A01H501Z']);
        $practice = Practice::factory()->create(['client_profile_id' => $other->id, 'branch_id' => $remote->id, 'notes' => 'Internal delegated practice notes']);
        $delegation = \App\Models\ClientDelegation::factory()->create(['client_profile_id' => $other->id, 'delegate_user_id' => $this->client->id, 'accepted_at' => now(), 'scopes' => ['view']]);
        $request = PracticeDocumentRequest::factory()->create(['practice_id' => $practice->id]);
        $public = PracticeDocument::factory()->create(['practice_id' => $practice->id, 'document_request_id' => $request->id, 'original_name' => 'delegated-secret-public.pdf']);
        PracticeDocument::factory()->create(['practice_id' => $practice->id, 'original_name' => 'delegated-secret-internal.pdf']);
        $this->assertTrue($this->client->can('practice-documents.download'));
        $this->actingAs($this->client, 'api')->getJson('/api/v1/operations/search?q=delegated-secret')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/operations/search?q=RSSMRC80A01H501Z')->assertOk()->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/v1/operations')->assertOk();
        $this->assertNotContains($practice->id, collect($response->json('data.practices.data'))->pluck('id')->all());
        $delegation->update(['scopes' => ['view', 'documents']]);
        $this->getJson('/api/v1/operations/search?q=delegated-secret')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->id)->assertDontSee('delegated-secret-internal.pdf');
    }
}
