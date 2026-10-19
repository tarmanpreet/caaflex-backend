<?php

namespace Tests\Feature;

use App\Models\ClientDelegation;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientDelegationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $delegate;

    private ClientProfile $profile;

    private Practice $practice;

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
        $this->profile = ClientProfile::factory()->forUser($this->owner)->create();
        $this->practice = Practice::factory()->create(['client_profile_id' => $this->profile->id]);
    }

    private function grant(array $scopes = ['view']): ClientDelegation
    {
        $this->actingAs($this->owner, 'api')->postJson('/api/v1/family/clients/'.$this->profile->id.'/delegations', ['email' => $this->delegate->email, 'scopes' => $scopes])->assertCreated();

        return ClientDelegation::firstOrFail();
    }

    public function test_invitation_requires_acceptance_and_revocation_removes_access_immediately(): void
    {
        $d = $this->grant(['view', 'message']);
        $url = '/api/v1/family/clients/'.$this->profile->id;
        $this->actingAs($this->delegate, 'api')->getJson($url)->assertForbidden();
        $this->getJson('/api/v1/family')->assertJsonCount(1, 'data.invitations');
        $this->postJson('/api/v1/family/delegations/'.$d->id.'/accept')->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('data.can_manage', false)->assertDontSee('fiscal_code')->assertDontSee('disk_path');
        $workspace = '/api/v1/practices/'.$this->practice->id.'/workspace';
        $this->getJson($workspace)->assertOk()->assertJsonPath('data.can_message', true)->assertJsonPath('data.can_upload', false);
        $this->postJson($workspace.'/messages', ['body' => 'Aggiornamento', 'visibility' => 'public'])->assertCreated();
        $this->actingAs($this->owner, 'api')->deleteJson($url.'/delegations/'.$d->id)->assertOk();
        $this->actingAs($this->delegate, 'api')->getJson($url)->assertForbidden();
        $this->getJson($workspace)->assertForbidden();
    }

    public function test_view_only_cannot_search_download_or_attach_documents_or_delegate_further(): void
    {
        $request = PracticeDocumentRequest::factory()->create(['practice_id' => $this->practice->id]);
        $doc = PracticeDocument::factory()->create(['practice_id' => $this->practice->id, 'document_request_id' => $request->id, 'original_name' => 'segreto.pdf']);
        $d = $this->grant(['view', 'message']);
        $d->update(['accepted_at' => now()]);
        $this->actingAs($this->delegate, 'api');
        $base = '/api/v1/practices/'.$this->practice->id.'/workspace';
        $this->getJson($base)->assertOk()->assertJsonCount(0, 'data.requests.0.documents');
        $this->getJson('/api/v1/operations/search?q=segreto')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($base.'/documents/'.$doc->id.'/download')->assertNotFound();
        $this->postJson($base.'/messages', ['file' => UploadedFile::fake()->create('test.pdf', 1, 'application/pdf'), 'visibility' => 'public'])->assertForbidden();
        $this->postJson($base.'/requests/'.$request->id.'/documents', ['file' => UploadedFile::fake()->create('test.pdf', 1, 'application/pdf')])->assertForbidden();
        $this->postJson('/api/v1/family/clients/'.$this->profile->id.'/delegations', ['email' => $this->owner->email, 'scopes' => ['view']])->assertForbidden();
    }

    public function test_expiration_and_new_invitation_require_reacceptance(): void
    {
        $d = $this->grant();
        $d->update(['accepted_at' => now(), 'expires_at' => now()->subSecond()]);
        $url = '/api/v1/family/clients/'.$this->profile->id;
        $this->actingAs($this->delegate, 'api')->getJson($url)->assertForbidden();
        $this->postJson('/api/v1/family/delegations/'.$d->id.'/accept')->assertUnprocessable();
        $this->grant(['view', 'documents', 'upload']);
        $this->actingAs($this->delegate, 'api')->getJson($url)->assertForbidden();
        $this->postJson('/api/v1/family/delegations/'.$d->id.'/accept')->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('data.can_documents', true);
    }

    public function test_scope_validation_other_recipient_and_web_page(): void
    {
        $this->actingAs($this->owner, 'api')->postJson('/api/v1/family/clients/'.$this->profile->id.'/delegations', ['email' => $this->delegate->email, 'scopes' => ['view', 'upload']])->assertUnprocessable();
        $d = $this->grant();
        $this->actingAs($this->owner, 'api')->postJson('/api/v1/family/delegations/'.$d->id.'/accept')->assertForbidden();
        $this->actingAs($this->owner, 'web')->get('/family/clients/'.$this->profile->id)->assertOk()->assertInertia(fn ($page) => $page->component('Clients/Family'));
    }
}
