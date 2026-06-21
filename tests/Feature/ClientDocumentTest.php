<?php

namespace Tests\Feature;

use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_document_record(): void
    {
        $profile = ClientProfile::factory()->create();
        $document = ClientDocument::factory()->create([
            'client_profile_id' => $profile->id,
        ]);

        $this->assertDatabaseHas('client_documents', [
            'id' => $document->id,
            'client_profile_id' => $profile->id,
            'original_name' => $document->original_name,
            'disk_path' => $document->disk_path,
        ]);
    }

    public function test_documents_cascade_delete_with_profile(): void
    {
        $profile = ClientProfile::factory()->create();
        $document = ClientDocument::factory()->create([
            'client_profile_id' => $profile->id,
        ]);

        // Verify document exists before deletion
        $this->assertDatabaseHas('client_documents', [
            'id' => $document->id,
        ]);

        // Delete the profile
        $profile->delete();

        // Document should also be deleted due to cascadeOnDelete
        $this->assertDatabaseMissing('client_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_uploaded_by_is_nullable(): void
    {
        $profile = ClientProfile::factory()->create();
        $document = ClientDocument::factory()->create([
            'client_profile_id' => $profile->id,
            'uploaded_by' => null,
        ]);

        $this->assertDatabaseHas('client_documents', [
            'id' => $document->id,
            'uploaded_by' => null,
        ]);

        $this->assertNull($document->uploaded_by);
    }
}
