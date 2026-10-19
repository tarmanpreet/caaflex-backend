<?php

namespace Tests\Feature;

use App\Models\ActivityEntry;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Models\PracticeMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PracticeWorkspaceApiTest extends TestCase
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
        $this->practice = Practice::factory()->create(['client_profile_id' => $profile->id]);
        Storage::fake('local');
        Notification::fake();
    }

    private function base(): string
    {
        return '/api/v1/practices/'.$this->practice->id.'/workspace';
    }

    public function test_customer_only_sees_public_messages_and_no_audit(): void
    {
        PracticeMessage::factory()->create(['practice_id' => $this->practice->id, 'body' => 'Messaggio cliente']);
        PracticeMessage::factory()->create(['practice_id' => $this->practice->id, 'body' => 'Segreto interno', 'visibility' => 'internal']);
        ActivityEntry::factory()->create(['practice_id' => $this->practice->id]);
        $this->actingAs($this->client, 'api')->getJson($this->base())->assertOk()->assertJsonCount(1, 'data.messages')->assertJsonCount(0, 'data.activity')->assertDontSee('Segreto interno')->assertJsonPath('data.can_manage', false);
        $this->actingAs($this->admin, 'api')->getJson($this->base())->assertOk()->assertJsonCount(2, 'data.messages')->assertJsonCount(1, 'data.activity');
    }

    public function test_customer_cannot_send_internal_messages_or_create_requests(): void
    {
        $this->actingAs($this->client, 'api')->postJson($this->base().'/messages', ['body' => 'Segreto', 'visibility' => 'internal'])->assertForbidden();
        $this->postJson($this->base().'/requests', ['title' => 'Documento'])->assertForbidden();
        $this->postJson($this->base().'/messages', ['body' => 'Richiesta informazioni', 'visibility' => 'public'])->assertCreated()->assertJsonCount(1, 'data.messages');
    }

    public function test_request_upload_rejection_replacement_and_approval(): void
    {
        $this->actingAs($this->admin, 'api')->postJson($this->base().'/requests', ['title' => 'Documento identità', 'instructions' => 'Entrambi i lati'])->assertCreated();
        $request = PracticeDocumentRequest::query()->sole();
        $upload = $this->base().'/requests/'.$request->id.'/documents';
        $this->actingAs($this->client, 'api')->post($upload, ['file' => UploadedFile::fake()->create('id.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();
        $doc = PracticeDocument::query()->sole();
        Storage::disk('local')->assertExists($doc->disk_path);
        $this->get($this->base().'/documents/'.$doc->id.'/download')->assertOk();
        $review = $upload.'/'.$doc->id.'/review';
        $this->patchJson($review, ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin, 'api')->patchJson($review, ['status' => 'rejected'])->assertUnprocessable();
        $this->patchJson($review, ['status' => 'rejected', 'reason' => 'Pagina mancante'])->assertOk()->assertJsonPath('data.requests.0.status', 'rejected');
        $this->actingAs($this->client, 'api')->post($upload, ['file' => UploadedFile::fake()->create('id-completo.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();
        $new = PracticeDocument::query()->latest('id')->first();
        $this->actingAs($this->admin, 'api')->patchJson($review, ['status' => 'approved'])->assertUnprocessable();
        $this->patchJson($upload.'/'.$new->id.'/review', ['status' => 'approved'])->assertOk()->assertJsonPath('data.requests.0.status', 'approved');
        $this->assertDatabaseHas('activity_entries', ['practice_id' => $this->practice->id, 'action' => 'document.approved']);
    }

    public function test_other_client_and_mismatched_nested_resources_are_rejected(): void
    {
        $other = Practice::factory()->create();
        $request = PracticeDocumentRequest::factory()->create(['practice_id' => $other->id]);
        $this->actingAs($this->client, 'api')->getJson('/api/v1/practices/'.$other->id.'/workspace')->assertForbidden();
        $this->post($this->base().'/requests/'.$request->id.'/documents', ['file' => UploadedFile::fake()->create('id.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_missing_file_returns_not_found_and_invalid_upload_is_rejected(): void
    {
        $request = PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id]);
        $doc = PracticeDocument::factory()->create(['practice_id' => $this->practice->id, 'document_request_id' => $request->id]);
        $this->actingAs($this->client, 'api')->getJson($this->base().'/documents/'.$doc->id.'/download')->assertNotFound();
        $this->post($this->base().'/requests/'.$request->id.'/documents', ['file' => UploadedFile::fake()->create('script.exe', 1)], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_customer_can_download_public_message_attachments_but_not_internal_ones(): void
    {
        $this->actingAs($this->admin, 'api')->post($this->base().'/messages', ['body' => 'Allegato pubblico', 'visibility' => 'public', 'file' => UploadedFile::fake()->create('pubblico.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.messages.0.document.original_name', 'pubblico.pdf');
        $public = PracticeDocument::query()->sole();
        $this->post($this->base().'/messages', ['body' => 'Nota interna', 'visibility' => 'internal', 'file' => UploadedFile::fake()->create('interno.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();
        $internal = PracticeDocument::query()->latest('id')->first();
        $this->actingAs($this->client, 'api')->get($this->base().'/documents/'.$public->id.'/download')->assertOk();
        $this->get($this->base().'/documents/'.$internal->id.'/download')->assertForbidden();
        $this->getJson($this->base())->assertJsonCount(1, 'data.messages')->assertDontSee('interno.pdf');
    }

    public function test_web_workspace_is_authorized_and_renders(): void
    {
        $this->actingAs($this->admin)->get('/practices/'.$this->practice->id.'/workspace')->assertInertia(fn ($page) => $page->component('Practices/Workspace')->where('workspace.practice.id', $this->practice->id));
    }

    public function test_activity_and_message_history_load_older_entries_without_leaking_internal_content(): void
    {
        \App\Models\ActivityEntry::factory()->count(55)->create(['practice_id' => $this->practice->id, 'client_profile_id' => $this->practice->client_profile_id]);
        \App\Models\PracticeMessage::factory()->count(55)->create(['practice_id' => $this->practice->id, 'visibility' => 'public']);
        $response = $this->actingAs($this->admin, 'api')->getJson($this->base())->assertOk()->assertJsonCount(50, 'data.activity')->assertJsonPath('data.has_older_activity', true)->assertJsonCount(50, 'data.messages')->assertJsonPath('data.has_older_messages', true);
        $cursor = collect($response->json('data.activity'))->min('id');
        $this->getJson($this->base().'?activity_before='.$cursor)->assertOk()->assertJsonCount(5, 'data.activity')->assertJsonPath('data.has_older_activity', false);
        $cursor = collect($response->json('data.messages'))->min('id');
        $this->getJson($this->base().'?before='.$cursor)->assertOk()->assertJsonCount(5, 'data.messages')->assertJsonPath('data.has_older_messages', false);
    }

    public function test_workspace_respects_the_existing_staff_upload_permission(): void
    {
        $staff = \App\Models\User::factory()->create(['is_active' => true]);
        $staff->givePermissionTo(['practices.view-any', 'practices.update']);
        $this->practice->assignedUsers()->attach($staff->id);
        $request = \App\Models\PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id]);
        $this->actingAs($staff, 'api')->getJson($this->base())->assertOk()->assertJsonPath('data.can_manage', true)->assertJsonPath('data.can_upload', false);
        $this->postJson($this->base().'/requests/'.$request->id.'/documents', ['file' => \Illuminate\Http\UploadedFile::fake()->create('qa.pdf', 1, 'application/pdf')])->assertForbidden();
    }

    public function test_cached_file_reauthorization_supports_head_requests(): void
    {
        $request = \App\Models\PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id]);
        $document = \App\Models\PracticeDocument::factory()->create(['practice_id' => $this->practice->id, 'document_request_id' => $request->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->put($document->disk_path, '%PDF synthetic');
        $this->actingAs($this->client, 'api')->call('HEAD', $this->base().'/documents/'.$document->id.'/download')->assertOk();
        $this->assertDatabaseHas('activity_entries', ['user_id' => $this->client->id, 'practice_id' => $this->practice->id, 'action' => 'document.downloaded']);
    }
}
