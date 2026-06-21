<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use App\Models\UserAvailability;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected ClientProfile $clientProfile;

    protected User $clientUser;

    protected User $anotherClientUser;

    protected ClientProfile $anotherClientProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->clientUser = User::factory()->create(['is_active' => true]);
        $this->clientUser->assignRole('cliente');
        $this->clientProfile = ClientProfile::factory()->create(['user_id' => $this->clientUser->id]);

        $this->anotherClientUser = User::factory()->create(['is_active' => true]);
        $this->anotherClientUser->assignRole('cliente');
        $this->anotherClientProfile = ClientProfile::factory()->create(['user_id' => $this->anotherClientUser->id]);
    }

    public function test_client_can_list_own_appointments(): void
    {
        $practiceType = PracticeType::factory()->create();

        Appointment::factory()->count(3)->create([
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
        ]);

        Appointment::factory()->count(2)->create([
            'client_profile_id' => $this->anotherClientProfile->id,
            'practice_type_id' => $practiceType->id,
        ]);

        $response = $this->actingAs($this->clientUser, 'api')
            ->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
            ]);
    }

    public function test_client_can_create_appointment(): void
    {
        $practiceType = PracticeType::factory()->create();

        $payload = [
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->clientUser, 'api')
            ->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Appuntamento creato.');

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
            'status' => 'da_confermare',
            'created_by' => $this->clientUser->id,
        ]);
    }

    public function test_client_cannot_create_appointment_for_another_client_profile(): void
    {
        $practiceType = PracticeType::factory()->create();

        $payload = [
            'client_profile_id' => $this->anotherClientProfile->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->clientUser, 'api')
            ->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_employee_can_create_appointment_for_client_profile(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');

        $practiceType = PracticeType::factory()->create();

        $payload = [
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($employee, 'api')
            ->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Appuntamento creato.')
            ->assertJsonPath('data.client_profile_id', $this->clientProfile->id)
            ->assertJsonPath('data.created_by', $employee->id);
    }

    public function test_client_cannot_fetch_practices_for_another_client_profile(): void
    {
        $practiceType = PracticeType::factory()->create();

        $response = $this->actingAs($this->clientUser, 'api')
            ->getJson('/api/v1/appointments-practices?client_id='.$this->anotherClientProfile->id.'&practice_type_id='.$practiceType->id);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_employee_can_fetch_practices_for_selected_client_profile(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');

        $practiceType = PracticeType::factory()->create();
        $practice = Practice::factory()->create([
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
        ]);

        $response = $this->actingAs($employee, 'api')
            ->getJson('/api/v1/appointments-practices?client_id='.$this->clientProfile->id.'&practice_type_id='.$practiceType->id);

        $response->assertOk()
            ->assertJsonPath('data.0.id', $practice->id);
    }

    public function test_employee_cannot_create_appointment_with_practice_from_different_client(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');

        $practiceType = PracticeType::factory()->create();
        $otherPractice = Practice::factory()->create([
            'client_profile_id' => $this->anotherClientProfile->id,
            'practice_type_id' => $practiceType->id,
        ]);

        $payload = [
            'client_profile_id' => $this->clientProfile->id,
            'practice_type_id' => $practiceType->id,
            'practice_id' => $otherPractice->id,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($employee, 'api')
            ->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['practice_id']);
    }

    public function test_client_can_cancel_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'client_profile_id' => $this->clientProfile->id,
            'status' => 'da_confermare',
        ]);

        $response = $this->actingAs($this->clientUser, 'api')
            ->deleteJson('/api/v1/appointments/'.$appointment->id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Appuntamento cancellato.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancellato',
        ]);
    }

    public function test_client_cannot_cancel_completed_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'client_profile_id' => $this->clientProfile->id,
            'status' => 'completato',
        ]);

        $response = $this->actingAs($this->clientUser, 'api')
            ->deleteJson('/api/v1/appointments/'.$appointment->id);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Impossibile cancellare un appuntamento completato.');
    }

    public function test_client_cannot_delete_others_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'client_profile_id' => $this->anotherClientProfile->id,
            'status' => 'da_confermare',
        ]);

        $response = $this->actingAs($this->clientUser, 'api')
            ->deleteJson('/api/v1/appointments/'.$appointment->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_appointments(): void
    {
        $response = $this->getJson('/api/v1/appointments');

        $response->assertStatus(401);
    }

    public function test_get_available_users(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        UserAvailability::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->clientUser, 'api')
            ->getJson('/api/v1/users/available');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    public function test_get_practice_types(): void
    {
        PracticeType::factory()->create(['name' => 'Type A']);
        PracticeType::factory()->create(['name' => 'Type B']);
        PracticeType::factory()->create(['name' => 'Type C']);

        $response = $this->actingAs($this->clientUser, 'api')
            ->getJson('/api/v1/practice-types');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_list_management_appointments_with_filters(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $assignedUser = User::factory()->create(['name' => 'Giulia Verdi', 'is_active' => true]);
        $assignedUser->assignRole('employee');

        $matchingClient = ClientProfile::factory()->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $otherClient = ClientProfile::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Bianchi',
        ]);

        $matchingType = PracticeType::factory()->create(['name' => 'ISEE', 'color' => '#2563EB']);
        $otherType = PracticeType::factory()->create(['name' => '730', 'color' => '#111111']);

        Appointment::factory()->create([
            'client_profile_id' => $matchingClient->id,
            'assigned_user_id' => $assignedUser->id,
            'practice_type_id' => $matchingType->id,
            'status' => 'confermato',
            'scheduled_at' => '2026-03-28 10:00:00',
            'duration_minutes' => 60,
        ]);

        Appointment::factory()->create([
            'client_profile_id' => $otherClient->id,
            'assigned_user_id' => $admin->id,
            'practice_type_id' => $otherType->id,
            'status' => 'da_confermare',
            'scheduled_at' => '2026-04-10 10:00:00',
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/appointments-manage?search=mario&status=confermato&assigned_user_id='.$assignedUser->id.'&from=2026-03-01&to=2026-03-31&per_page=20');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client.first_name', 'Mario')
            ->assertJsonPath('data.0.practice_type.name', 'ISEE')
            ->assertJsonPath('data.0.assigned_user.name', 'Giulia Verdi')
            ->assertJsonStructure([
                'current_page',
                'data',
                'per_page',
                'total',
                'last_page',
                'next_page_url',
            ]);
    }

    public function test_employee_management_list_is_scoped_to_assigned_appointments(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');

        $otherEmployee = User::factory()->create(['is_active' => true]);
        $otherEmployee->assignRole('employee');

        $client = ClientProfile::factory()->create(['first_name' => 'Luca', 'last_name' => 'Neri']);
        $practiceType = PracticeType::factory()->create(['name' => 'ISEE']);

        Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'assigned_user_id' => $employee->id,
            'practice_type_id' => $practiceType->id,
            'status' => 'confermato',
        ]);

        Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'assigned_user_id' => $otherEmployee->id,
            'practice_type_id' => $practiceType->id,
            'status' => 'confermato',
        ]);

        $response = $this->actingAs($employee, 'api')
            ->getJson('/api/v1/appointments-manage');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.assigned_user.id', $employee->id);
    }
}
