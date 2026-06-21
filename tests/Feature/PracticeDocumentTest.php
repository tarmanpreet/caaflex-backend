<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\PracticeDocumentController;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PracticeDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        // Register routes for testing (actual routes defined in Task 10)
        Route::middleware('web')->prefix('api/v1')->group(function () {
            Route::post('/practices/{practice}/documents', [PracticeDocumentController::class, 'store']);
            Route::get('/practices/{practice}/documents/{document}/download', [PracticeDocumentController::class, 'download']);
            Route::delete('/practices/{practice}/documents/{document}', [PracticeDocumentController::class, 'destroy']);
        });
    }

    public function test_admin_can_upload_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/practices/'.$practice->id.'/documents', [
                'files' => [$file],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Documents uploaded.',
            ]);

        $this->assertDatabaseHas('practice_documents', [
            'practice_id' => $practice->id,
            'uploaded_by' => $admin->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_employee_can_upload_document(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $practice = Practice::factory()->create();

        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($employee)
            ->postJson('/api/v1/practices/'.$practice->id.'/documents', [
                'files' => [$file],
                'descriptions' => ['Quarterly report'],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('practice_documents', [
            'practice_id' => $practice->id,
            'uploaded_by' => $employee->id,
            'original_name' => 'report.pdf',
            'description' => 'Quarterly report',
        ]);
    }

    public function test_employee_cannot_upload_to_unassigned_practice(): void
    {
        Storage::fake('local');

        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $practice = Practice::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($cliente)
            ->postJson('/api/v1/practices/'.$practice->id.'/documents', [
                'files' => [$file],
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $filePath = "practice-documents/{$practice->id}/test-file.pdf";
        Storage::disk('local')->put($filePath, 'fake content');

        $document = PracticeDocument::factory()->create([
            'practice_id' => $practice->id,
            'uploaded_by' => $admin->id,
            'disk_path' => $filePath,
        ]);

        Storage::disk('local')->assertExists($filePath);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/v1/practices/'.$practice->id.'/documents/'.$document->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Document deleted.',
            ]);

        Storage::disk('local')->assertMissing($filePath);

        $this->assertDatabaseMissing('practice_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_employee_cannot_delete_document(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $practice = Practice::factory()->create();

        $document = PracticeDocument::factory()->create([
            'practice_id' => $practice->id,
            'uploaded_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)
            ->deleteJson('/api/v1/practices/'.$practice->id.'/documents/'.$document->id);

        $response->assertStatus(403);
    }

    public function test_download_returns_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $filePath = "practice-documents/{$practice->id}/test-download.pdf";
        Storage::disk('local')->put($filePath, 'fake pdf content');

        $document = PracticeDocument::factory()->create([
            'practice_id' => $practice->id,
            'uploaded_by' => $admin->id,
            'disk_path' => $filePath,
            'original_name' => 'my-document.pdf',
        ]);

        $response = $this->actingAs($admin)
            ->get('/api/v1/practices/'.$practice->id.'/documents/'.$document->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
