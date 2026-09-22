<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use App\Notifications\DomainNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebApiParityContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_client_creation_is_parity_between_web_and_api(): void
    {
        $payload = [
            'first_name' => 'Giulia',
            'last_name' => 'Bianchi',
            'phone' => '+39 333 123 4567',
            'date_of_birth' => '1990-05-01',
            'email' => 'giulia@example.com',
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'postal_code' => '20121',
            'notes' => 'Cliente contratto di parita.',
        ];

        $this->actingAs($this->admin)
            ->post('/clients', $payload)
            ->assertStatus(302);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/clients', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.created_by', $this->admin->id);

        $clients = ClientProfile::query()->orderBy('id')->get();
        $this->assertCount(2, $clients);
        [$webClient, $apiClient] = $clients->all();

        $this->assertSame($webClient->first_name, $apiClient->first_name);
        $this->assertSame($webClient->last_name, $apiClient->last_name);
        $this->assertSame($webClient->phone, $apiClient->phone);
        $this->assertSame((string) $webClient->date_of_birth, (string) $apiClient->date_of_birth);
        $this->assertSame($webClient->created_by, $apiClient->created_by);
        $this->assertSame($this->admin->id, $apiClient->created_by);
    }

    public function test_client_creation_authorization_is_parity_between_web_and_api(): void
    {
        $outsider = User::factory()->create(['is_active' => true]);

        $payload = [
            'first_name' => 'Marco',
            'last_name' => 'Verdi',
            'phone' => '+39 344 765 4321',
            'date_of_birth' => '1985-11-23',
        ];

        $this->actingAs($outsider)
            ->post('/clients', $payload)
            ->assertStatus(403);

        $this->actingAs($outsider, 'api')
            ->postJson('/api/v1/clients', $payload)
            ->assertStatus(403);

        $this->assertSame(0, ClientProfile::query()->count());
    }

    public function test_practice_creation_is_parity_between_web_and_api(): void
    {
        $client = ClientProfile::factory()->create();

        $payload = [
            'client_profile_id' => $client->id,
            'type' => '730',
            'status' => 'nuova',
            'reference_year' => 2025,
        ];

        $this->actingAs($this->admin)
            ->postJson('/practices', $payload)
            ->assertStatus(302);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/practices', $payload)
            ->assertStatus(201);

        $practices = Practice::query()->orderBy('id')->get();
        $this->assertCount(2, $practices);
        [$webPractice, $apiPractice] = $practices->all();

        $this->assertSame($webPractice->client_profile_id, $apiPractice->client_profile_id);
        $this->assertSame($webPractice->type, $apiPractice->type);
        $this->assertSame($webPractice->status, $apiPractice->status);
        $this->assertSame($webPractice->reference_year, $apiPractice->reference_year);
        $this->assertSame($webPractice->created_by, $apiPractice->created_by);
        $this->assertSame($this->admin->id, $apiPractice->created_by);
    }

    public function test_deadline_completion_produces_parity_domain_state_and_notifications(): void
    {
        $assignee = User::factory()->create(['is_active' => true]);

        $webPractice = Practice::factory()->create();
        $webDeadline = PracticeDeadline::factory()->create([
            'practice_id' => $webPractice->id,
            'user_id' => $assignee->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->patch("/practices/{$webPractice->id}/deadlines/{$webDeadline->id}/complete")
            ->assertStatus(302);

        $apiPractice = Practice::factory()->create();
        $apiDeadline = PracticeDeadline::factory()->create([
            'practice_id' => $apiPractice->id,
            'user_id' => $assignee->id,
            'status' => PracticeDeadline::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/practices/{$apiPractice->id}/deadlines/{$apiDeadline->id}/complete")
            ->assertStatus(200);

        $webDeadline->refresh();
        $apiDeadline->refresh();

        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $webDeadline->status);
        $this->assertSame(PracticeDeadline::STATUS_COMPLETED, $apiDeadline->status);

        $this->assertSame(
            $this->statusChangeNotificationsCount($assignee, $apiDeadline),
            $this->statusChangeNotificationsCount($assignee, $webDeadline),
        );
        $this->assertSame(1, $this->statusChangeNotificationsCount($assignee, $webDeadline));
    }

    public function test_notification_read_is_parity_between_web_and_api(): void
    {
        $this->admin->notify(new DomainNotification('web-api-parity', ['title' => 'Notifica A', 'body' => 'Corpo A'], ['database']));
        $this->admin->notify(new DomainNotification('web-api-parity', ['title' => 'Notifica B', 'body' => 'Corpo B'], ['database']));

        [$webNotification, $apiNotification] = $this->admin->notifications()->get()->all();

        $this->actingAs($this->admin)
            ->post("/notifications/{$webNotification->getKey()}/read")
            ->assertStatus(200);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/notifications/{$apiNotification->id}/read")
            ->assertStatus(200);

        $webNotification->refresh();
        $apiNotification->refresh();

        $this->assertNotNull($webNotification->read_at);
        $this->assertNotNull($apiNotification->read_at);
    }

    private function statusChangeNotificationsCount(User $recipient, PracticeDeadline $deadline): int
    {
        $rows = DB::table('notifications')
            ->where('notifiable_id', $recipient->id)
            ->where('notifiable_type', User::class)
            ->get();

        return $rows
            ->filter(function (object $row) use ($deadline): bool {
                $data = json_decode($row->data, true) ?? ['event_key' => null, 'subject_id' => null];

                return ($data['event_key'] ?? null) === 'deadlines.status_changed'
                    && (int) ($data['subject_id'] ?? 0) === $deadline->id;
            })
            ->count();
    }
}
