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
            ]);

        $response->assertRedirect();

        Storage::disk('local')->assertExists("client-documents/{$client->id}/".$file->hashName());

        $this->assertDatabaseHas('client_documents', [
            'client_profile_id' => $client->id,
            'uploaded_by' => $employee->id,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'description' => 'Test document',
        ]);
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
