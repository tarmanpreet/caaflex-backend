<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeNote;
use App\Models\PracticeStatusLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenPracticeDetailApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_customer_can_read_own_practice_without_internal_data(): void
    {
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('cliente');
        $profile = ClientProfile::factory()->forUser($customer)->create();
        $practice = Practice::factory()->create([
            'client_profile_id' => $profile->id,
            'notes' => 'Confidential internal note',
        ]);
        PracticeDeadline::factory()->create(['practice_id' => $practice->id, 'notes' => 'Internal task']);
        PracticeNote::factory()->create(['practice_id' => $practice->id, 'body' => 'Staff only']);
        PracticeStatusLog::create([
            'practice_id' => $practice->id,
            'user_id' => $customer->id,
            'old_status' => 'nuova',
            'new_status' => 'in_lavorazione',
        ]);

        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/my-practices/{$practice->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $practice->id)
            ->assertJsonPath('data.tracking_code', $practice->tracking_code)
            ->assertJsonPath('data.status_history.0.new_status', 'in_lavorazione')
            ->assertJsonMissingPath('data.notes')
            ->assertJsonMissingPath('data.deadlines')
            ->assertJsonMissingPath('data.assigned_users')
            ->assertJsonMissingPath('data.documents')
            ->assertJsonMissingPath('data.status_history.0.user_id');
    }

    public function test_customer_cannot_read_another_customers_practice_or_staff_detail(): void
    {
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('cliente');
        ClientProfile::factory()->forUser($customer)->create();
        $otherPractice = Practice::factory()->create();

        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/my-practices/{$otherPractice->id}")
            ->assertNotFound();

        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/practices/{$otherPractice->id}")
            ->assertForbidden();
    }

    public function test_anonymous_and_unlinked_users_cannot_read_customer_detail(): void
    {
        $practice = Practice::factory()->create();

        $this->getJson("/api/v1/my-practices/{$practice->id}")->assertUnauthorized();

        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('cliente');
        $this->actingAs($customer, 'api')
            ->getJson("/api/v1/my-practices/{$practice->id}")
            ->assertNotFound();
    }
}
