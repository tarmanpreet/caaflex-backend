<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmedMail;
use App\Models\Appointment;
use App\Models\AutoConfirmSlot;
use App\Models\ClientProfile;
use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected User $anotherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');

        $this->anotherEmployee = User::factory()->create();
        $this->anotherEmployee->assignRole('employee');
    }

    public function test_admin_can_view_all_appointments(): void
    {
        $practiceType = PracticeType::factory()->create();

        Appointment::factory()->count(3)->create([
            'practice_type_id' => $practiceType->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/appointments')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/Index')
                ->has('appointments.data', 3)
            );
    }

    public function test_employee_sees_only_own_appointments(): void
    {
        Appointment::factory()->create(['assigned_user_id' => $this->employee->id]);
        Appointment::factory()->create(['assigned_user_id' => $this->anotherEmployee->id]);

        $this->actingAs($this->employee)
            ->get('/appointments')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/Index')
                ->has('appointments.data', 1)
            );
    }

    public function test_admin_can_create_appointment(): void
    {
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create();

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => now()->addDays(30)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $response->assertStatus(302);

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'status' => 'da_confermare',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_confirm_appointment_and_creates_practice(): void
    {
        $practiceType = PracticeType::factory()->create();
        $client = ClientProfile::factory()->create(['email' => 'client@example.com']);

        $appointment = Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'practice_id' => null,
            'status' => 'da_confermare',
        ]);

        $this->actingAs($this->admin)
            ->patch('/appointments/'.$appointment->id, [
                'status' => 'confermato',
            ]);

        $appointment->refresh();

        $this->assertNotNull($appointment->practice_id);

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $client->id,
            'type' => $practiceType->name,
            'practice_type_id' => $practiceType->id,
            'status' => 'nuova',
        ]);

        Mail::assertQueued(AppointmentConfirmedMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id;
        });
    }

    public function test_admin_can_assign_user_to_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => 'da_confermare',
        ]);

        $this->actingAs($this->admin)
            ->patch('/appointments/'.$appointment->id, [
                'assigned_user_id' => $this->employee->id,
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'assigned_user_id' => $this->employee->id,
        ]);
    }

    public function test_admin_can_delete_appointment(): void
    {
        $appointment = Appointment::factory()->create();

        $this->actingAs($this->admin)
            ->delete('/appointments/'.$appointment->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }

    public function test_employee_cannot_delete_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'assigned_user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employee)
            ->delete('/appointments/'.$appointment->id)
            ->assertStatus(403);
    }

    public function test_employee_cannot_view_others_appointments(): void
    {
        Appointment::factory()->create(['assigned_user_id' => $this->anotherEmployee->id]);

        $this->actingAs($this->employee)
            ->get('/appointments')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/Index')
                ->has('appointments.data', 0)
            );
    }

    public function test_appointment_in_auto_confirm_slot_is_confirmed(): void
    {
        $client = ClientProfile::factory()->create(['email' => 'client@example.com']);
        $practiceType = PracticeType::factory()->create();

        AutoConfirmSlot::factory()->create([
            'day_of_week' => 1,
            'time_from' => '09:00:00',
            'time_to' => '17:00:00',
        ]);

        $monday = now()->addDays(2)->startOfDay();
        while ($monday->dayOfWeek !== 1) {
            $monday->addDay();
        }

        $scheduledAt = $monday->setTime(10, 0)->format('Y-m-d H:i');

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 60,
        ];

        $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $client->id,
            'status' => 'confermato',
        ]);

        Mail::assertQueued(AppointmentConfirmedMail::class);
    }

    public function test_auto_confirm_creates_practice_automatically(): void
    {
        $client = ClientProfile::factory()->create(['email' => 'client@example.com']);
        $practiceType = PracticeType::factory()->create();

        AutoConfirmSlot::factory()->create([
            'day_of_week' => 1,
            'time_from' => '09:00:00',
            'time_to' => '17:00:00',
        ]);

        $monday = now()->addDays(2)->startOfDay();
        while ($monday->dayOfWeek !== 1) {
            $monday->addDay();
        }

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => $monday->setTime(10, 0)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
        ];

        $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $this->assertDatabaseHas('practices', [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'status' => 'nuova',
        ]);
    }

    public function test_appointment_outside_auto_confirm_slot_remains_pending(): void
    {
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create();

        AutoConfirmSlot::factory()->create([
            'day_of_week' => 1,
            'time_from' => '09:00:00',
            'time_to' => '12:00:00',
        ]);

        $monday = now()->addDays(2)->startOfDay();
        while ($monday->dayOfWeek !== 1) {
            $monday->addDay();
        }

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => $monday->setTime(14, 0)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
        ];

        $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $client->id,
            'status' => 'da_confermare',
        ]);
    }

    public function test_appointment_without_auto_confirm_slot_remains_pending(): void
    {
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create();

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => now()->addDays(30)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
        ];

        $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $client->id,
            'status' => 'da_confermare',
        ]);
    }

    public function test_appointment_on_wrong_day_is_not_auto_confirmed(): void
    {
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create();

        AutoConfirmSlot::factory()->create([
            'day_of_week' => 1,
            'time_from' => '09:00:00',
            'time_to' => '17:00:00',
        ]);

        $friday = now()->addDays(2)->startOfDay();
        while ($friday->dayOfWeek !== 5) {
            $friday->addDay();
        }

        $payload = [
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => $friday->setTime(10, 0)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
        ];

        $this->actingAs($this->admin)
            ->post('/appointments', $payload);

        $this->assertDatabaseHas('appointments', [
            'client_profile_id' => $client->id,
            'status' => 'da_confermare',
        ]);
    }
}
