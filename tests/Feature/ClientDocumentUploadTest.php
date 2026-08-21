<?php

namespace Tests\Feature;

use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();
    }

    public function test_employee_can_upload_document(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $client = ClientProfile::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($employee)
            ->post('/clients/'.$client->id.'/documents', [
                'files' => [$file],
                'descriptions' => ['Test document'],
                'expires_on' => ['2027-04-30'],
            ]);

        $response->assertRedirect();

        Storage::disk('local')->assertExists("client-documents/{$client->id}/".$file->hashName());

        $this->assertDatabaseHas('client_documents', [
            'client_profile_id' => $client->id,
            'uploaded_by' => $employee->id,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'description' => 'Test document',
            'expires_on' => '2027-04-30',
        ]);
    }

    public function test_employee_can_upload_multiple_documents_with_independent_expirations(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $client = ClientProfile::factory()->create();
        $firstFile = UploadedFile::fake()->create('first.pdf', 100, 'application/pdf');
        $secondFile = UploadedFile::fake()->create('second.pdf', 100, 'application/pdf');

        $this->actingAs($employee)
            ->post(route('clients.documents.store', $client), [
                'files' => [$firstFile, $secondFile],
                'descriptions' => ['Primo', 'Secondo'],
                'expires_on' => ['2027-01-15', null],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_documents', [
            'original_name' => 'first.pdf',
            'expires_on' => '2027-01-15',
        ]);
        $this->assertDatabaseHas('client_documents', [
            'original_name' => 'second.pdf',
            'expires_on' => null,
        ]);
    }

    public function test_invalid_expiration_rejects_upload_without_storing_file(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $client = ClientProfile::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($employee)
            ->post(route('clients.documents.store', $client), [
                'files' => [$file],
                'expires_on' => ['not-a-date'],
            ])
            ->assertSessionHasErrors('expires_on.0');

        $this->assertDatabaseCount('client_documents', 0);
        Storage::disk('local')->assertMissing("client-documents/{$client->id}/".$file->hashName());
    }

    public function test_employee_can_update_and_clear_document_expiration(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $client = ClientProfile::factory()->create();
        $document = ClientDocument::factory()->create(['client_profile_id' => $client->id]);

        $this->actingAs($employee)
            ->patch(route('clients.documents.expiration.update', [$client, $document]), [
                'expires_on' => '2027-05-20',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_documents', [
            'id' => $document->id,
            'expires_on' => '2027-05-20',
        ]);

        $this->actingAs($employee)
            ->patch(route('clients.documents.expiration.update', [$client, $document]), [
                'expires_on' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_documents', [
            'id' => $document->id,
            'expires_on' => null,
        ]);
    }

    public function test_document_routes_reject_a_document_from_another_client(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();
        $otherClient = ClientProfile::factory()->create();
        $document = ClientDocument::factory()->create(['client_profile_id' => $otherClient->id]);

        $this->actingAs($admin)
            ->patch(route('clients.documents.expiration.update', [$client, $document]), [
                'expires_on' => '2027-05-20',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('clients.documents.destroy', [$client, $document]))
            ->assertNotFound();

        $this->assertModelExists($document);
    }

    public function test_employee_cannot_delete_document(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $client = ClientProfile::factory()->create();

        $document = ClientDocument::factory()->create([
            'client_profile_id' => $client->id,
            'uploaded_by' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->delete('/clients/'.$client->id.'/documents/'.$document->id)
            ->assertStatus(403);
    }

    public function test_admin_can_delete_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create();

        $filePath = "client-documents/{$client->id}/test-file.pdf";
        Storage::disk('local')->put($filePath, 'fake content');

        $document = ClientDocument::factory()->create([
            'client_profile_id' => $client->id,
            'uploaded_by' => $admin->id,
            'disk_path' => $filePath,
        ]);

        Storage::disk('local')->assertExists($filePath);

        $response = $this->actingAs($admin)
            ->delete('/clients/'.$client->id.'/documents/'.$document->id);

        $response->assertRedirect();

        Storage::disk('local')->assertMissing($filePath);

        $this->assertDatabaseMissing('client_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_unsupported_file_type_rejected(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $client = ClientProfile::factory()->create();

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($employee)
            ->post('/clients/'.$client->id.'/documents', [
                'files' => [$file],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('files.0');
    }

    public function test_admin_can_download_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = ClientProfile::factory()->create();

        $filePath = "client-documents/{$client->id}/test-download.pdf";
        Storage::disk('local')->put($filePath, 'fake pdf content');

        $document = ClientDocument::factory()->create([
            'client_profile_id' => $client->id,
            'uploaded_by' => $admin->id,
            'disk_path' => $filePath,
            'original_name' => 'my-document.pdf',
        ]);

        $response = $this->actingAs($admin)
            ->get('/clients/'.$client->id.'/documents/'.$document->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
