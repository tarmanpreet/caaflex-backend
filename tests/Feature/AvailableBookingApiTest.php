<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientDelegation;
use App\Models\ClientProfile;
use App\Models\PracticeType;
use App\Models\User;
use App\Models\UserAvailability;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AvailableBookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private ClientProfile $profile;

    private PracticeType $type;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        $this->client = User::factory()->create(['is_active' => true]);
        $this->client->assignRole('cliente');
        $this->worker = User::factory()->create(['is_active' => true]);
        $this->worker->assignRole('employee');
        $this->profile = ClientProfile::factory()->forUser($this->client)->create();
        $this->type = PracticeType::factory()->create(['duration_minutes' => 30]);
        $this->worker->practiceTypes()->attach($this->type->id);
        $this->date = now()->addDay()->toDateString();
        UserAvailability::factory()->create(['user_id' => $this->worker->id, 'day_of_week' => now()->addDay()->dayOfWeek, 'time_from' => '09:00:00', 'time_to' => '11:00:00']);
    }

    private function slots(?Appointment $appointment = null): array
    {
        return $this->actingAs($this->client, 'api')->getJson('/api/v1/booking/slots?'.http_build_query(['client_profile_id' => $this->profile->id, 'practice_type_id' => $this->type->id, 'date' => $this->date, 'appointment_id' => $appointment?->id]))->assertOk()->json('data');
    }

    public function test_slots_exclude_overlaps_and_duplicate_booking_is_rejected(): void
    {
        $slots = $this->slots();
        $this->assertCount(7, $slots);
        $token = $slots[0]['token'];
        $response = $this->postJson('/api/v1/booking', ['slot' => $token])->assertCreated();
        $id = $response->json('data.id');
        $this->postJson('/api/v1/booking', ['slot' => $token])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        $remaining = $this->slots();
        $this->assertCount(5, $remaining);
        $this->assertStringContainsString('09:30:00', $remaining[0]['scheduled_at']);
        $newSlots = $this->slots(Appointment::findOrFail($id));
        $this->postJson('/api/v1/booking/'.$id.'/reschedule', ['slot' => $newSlots[4]['token']])->assertOk();
        $this->assertStringContainsString('10:00:00', Appointment::findOrFail($id)->scheduled_at->toDateTimeString());
    }

    public function test_tampering_expiry_and_changed_availability_are_rejected(): void
    {
        $token = $this->slots()[0]['token'];
        $this->postJson('/api/v1/booking', ['slot' => $token.'x'])->assertUnprocessable();
        UserAvailability::query()->delete();
        $this->postJson('/api/v1/booking', ['slot' => $token])->assertUnprocessable();
        $this->travel(16)->minutes();
        $this->postJson('/api/v1/booking', ['slot' => $token])->assertUnprocessable();
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_delegation_is_rechecked_when_booking_and_tokens_are_bound_to_recipient(): void
    {
        $delegate = User::factory()->create(['is_active' => true]);
        $delegate->assignRole('cliente');
        $d = ClientDelegation::factory()->create(['client_profile_id' => $this->profile->id, 'delegate_user_id' => $delegate->id, 'scopes' => ['view', 'book'], 'accepted_at' => now()]);
        $token = $this->slots()[0]['token'];
        $this->actingAs($delegate, 'api')->postJson('/api/v1/booking', ['slot' => $token])->assertUnprocessable();
        $token = $this->getJson('/api/v1/booking/slots?'.http_build_query(['client_profile_id' => $this->profile->id, 'practice_type_id' => $this->type->id, 'date' => $this->date]))->assertOk()->json('data.0.token');
        $d->update(['revoked_at' => now()]);
        $this->postJson('/api/v1/booking', ['slot' => $token])->assertForbidden();
    }

    public function test_existing_action_also_checks_overlaps_and_adjacent_times_are_allowed(): void
    {
        $data = ['client_profile_id' => $this->profile->id, 'practice_type_id' => $this->type->id, 'assigned_user_id' => $this->worker->id, 'scheduled_at' => $this->date.' 09:00:00', 'duration_minutes' => 30];
        $action = app(\App\Actions\Appointment\StoreAppointmentAction::class);
        $action->execute($data, $this->worker->id);
        $action->execute(array_replace($data, ['scheduled_at' => $this->date.' 09:30:00']), $this->worker->id);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $action->execute(array_replace($data, ['scheduled_at' => $this->date.' 09:15:00']), $this->worker->id);
    }

    public function test_notes_can_be_updated_for_existing_overlapping_appointments(): void
    {
        $data = ['client_profile_id' => $this->profile->id, 'practice_type_id' => $this->type->id, 'assigned_user_id' => $this->worker->id, 'scheduled_at' => $this->date.' 09:00:00', 'duration_minutes' => 30, 'status' => 'da_confermare'];
        $appointment = Appointment::factory()->create($data);
        Appointment::factory()->create($data);
        $action = app(\App\Actions\Appointment\UpdateAppointmentAction::class);

        $updated = $action->execute(['notes' => 'Nota aggiornata', 'scheduled_at' => $data['scheduled_at']], $appointment, $this->worker->id);
        $this->assertSame('Nota aggiornata', $updated->fresh()->notes);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $action->execute(['scheduled_at' => $this->date.' 09:15:00'], $updated, $this->worker->id);
    }

    public function test_options_are_scoped_and_web_page_is_available(): void
    {
        ClientProfile::factory()->create();
        $this->actingAs($this->client, 'api')->getJson('/api/v1/booking/options?client_profile_id='.$this->profile->id)->assertOk()->assertJsonCount(1, 'data.clients.data');
        $this->actingAs($this->client, 'web')->get('/booking')->assertOk()->assertInertia(fn ($page) => $page->component('Appointments/Booking'));
    }

    public function test_inactive_staff_changed_duration_and_changed_service_cannot_reuse_a_slot(): void
    {
        $slot = $this->slots()[0];
        $this->worker->update(['is_active' => false]);
        $this->postJson('/api/v1/booking', ['slot' => $slot['token']])->assertUnprocessable();
        $this->worker->update(['is_active' => true]);
        $this->type->update(['duration_minutes' => 45]);
        $this->postJson('/api/v1/booking', ['slot' => $slot['token']])->assertUnprocessable();
        $this->type->update(['duration_minutes' => 30]);
        $id = $this->postJson('/api/v1/booking', ['slot' => $slot['token']])->assertCreated()->json('data.id');
        $other = \App\Models\PracticeType::factory()->create();
        $this->getJson('/api/v1/booking/slots?'.http_build_query(['client_profile_id' => $this->profile->id, 'practice_type_id' => $other->id, 'date' => $this->date, 'appointment_id' => $id]))->assertUnprocessable();
    }

    public function test_rescheduling_rechecks_service_after_a_token_has_been_issued(): void
    {
        $id = $this->postJson('/api/v1/booking', ['slot' => $this->slots()[0]['token']])->assertCreated()->json('data.id');
        $appointment = Appointment::findOrFail($id);
        $token = $this->slots($appointment)[3]['token'];
        $changedType = PracticeType::factory()->create(['duration_minutes' => 30]);
        $appointment->update(['practice_type_id' => $changedType->id]);
        $this->postJson('/api/v1/booking/'.$id.'/reschedule', ['slot' => $token])->assertUnprocessable();
        $this->assertSame($changedType->id, $appointment->fresh()->practice_type_id);
    }

    public function test_absence_hides_slots_and_invalidates_previously_issued_slot_tokens(): void
    {
        $initial = $this->slots();
        \App\Models\EmployeeAbsence::factory()->create(['user_id' => $this->worker->id, 'type' => 'malattia', 'status' => 'reported', 'all_day' => false, 'starts_at' => $this->date.' 09:00:00', 'ends_at' => $this->date.' 10:00:00']);
        $this->assertCount(3, $this->slots());
        $this->postJson('/api/v1/booking', ['slot' => $initial[0]['token']])->assertUnprocessable();
        $this->postJson('/api/v1/booking', ['slot' => $initial[4]['token']])->assertCreated();
    }
}
