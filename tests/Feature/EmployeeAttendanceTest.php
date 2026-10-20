<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Branch;
use App\Models\EmployeeAbsence;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $admin;

    private User $superadmin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();
        $this->employee = $this->worker('employee');
        $this->admin = $this->worker('admin');
        $this->superadmin = $this->worker('superadmin');
        $this->client = $this->worker('cliente');
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(8, 0));
    }

    private function worker(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['type' => 'ferie', 'start_date' => '2026-11-02', 'end_date' => '2026-11-03', 'all_day' => true, 'note' => 'Nota di prova'], $overrides);
    }

    public function test_all_workers_including_admins_can_clock_and_take_breaks(): void
    {
        foreach ([$this->employee, $this->admin, $this->superadmin] as $worker) {
            $this->actingAs($worker, 'api')->postJson('/api/v1/employees/clock-out')->assertUnprocessable();
            $this->postJson('/api/v1/employees/clock-in', ['note' => 'Remoto'])->assertCreated();
            $this->postJson('/api/v1/employees/clock-in')->assertUnprocessable();
            $this->travel(2)->hours();
            $this->postJson('/api/v1/employees/clock-out')->assertOk();
            $this->postJson('/api/v1/employees/clock-out')->assertUnprocessable();
            $this->postJson('/api/v1/employees/clock-in')->assertCreated();
            $this->travel(1)->hours();
            $this->postJson('/api/v1/employees/clock-out')->assertOk();
            $this->getJson('/api/v1/employees?month=2026-10')->assertOk()->assertJsonPath('data.worked_minutes', 180)->assertJsonCount(2, 'data.sessions');
        }
    }

    public function test_client_guest_inactive_and_roleless_users_cannot_access(): void
    {
        $this->getJson('/api/v1/employees')->assertUnauthorized();
        $this->actingAs($this->client, 'api')->getJson('/api/v1/employees')->assertForbidden();
        $this->postJson('/api/v1/employees/clock-in')->assertForbidden();
        $this->postJson('/api/v1/employees/absences', $this->payload())->assertForbidden();
        $this->actingAs($this->client, 'web')->get('/employees')->assertForbidden();
        $this->employee->update(['is_active' => false]);
        $this->actingAs($this->employee, 'api')->getJson('/api/v1/employees')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active' => true]), 'api')->getJson('/api/v1/employees')->assertForbidden();
    }

    public function test_ownership_cannot_be_forged_and_clients_are_not_listed(): void
    {
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/clock-in', ['user_id' => $this->admin->id, 'started_at' => '2020-01-01'])->assertCreated()->assertJsonPath('data.user_id', $this->employee->id);
        $this->getJson('/api/v1/employees?user_id='.$this->admin->id)->assertNotFound();
        $this->actingAs($this->superadmin, 'api')->getJson('/api/v1/employees')->assertOk()->assertJsonCount(3, 'data.staff')->assertDontSee($this->client->name);
    }

    public function test_web_and_api_share_the_same_data_and_validation(): void
    {
        $this->actingAs($this->employee, 'web')->get('/employees')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Employees/Index')->where('attendance.target.id', $this->employee->id));
        $this->post('/employees/clock-in')->assertRedirect();
        $this->actingAs($this->employee, 'api')->getJson('/api/v1/employees')->assertJsonPath('data.open_session.user_id', $this->employee->id);
        $this->getJson('/api/v1/employees?month=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/employees?month=2026-13')->assertUnprocessable();
        $this->actingAs($this->employee, 'web')->post('/employees/absences', $this->payload())->assertRedirect();
        $this->actingAs($this->employee, 'api')->getJson('/api/v1/employees?month=2026-11')->assertJsonCount(1, 'data.absences');
    }

    public function test_future_absences_can_be_requested_while_currently_clocked_in(): void
    {
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/clock-in')->assertCreated();
        $this->postJson('/api/v1/employees/absences', $this->payload())->assertCreated();
    }

    public function test_absence_dates_hours_overlaps_and_unknown_types_are_validated(): void
    {
        $this->actingAs($this->employee, 'api');
        foreach ([['end_date' => '2026-10-01'], ['type' => 'unknown'], ['start_date' => '2026-02-30'], ['all_day' => false], ['all_day' => false, 'time_from' => '15:00', 'time_to' => '14:00', 'end_date' => '2026-11-02'], ['certificate_number' => 'NOT-SICK'], ['start_date' => '2020-01-01']] as $invalid) {
            $this->postJson('/api/v1/employees/absences', $this->payload($invalid))->assertUnprocessable();
        }
        $this->postJson('/api/v1/employees/absences', $this->payload(['type' => 'permesso', 'all_day' => false, 'time_from' => '09:00', 'time_to' => '10:00', 'end_date' => '2026-11-02']))->assertCreated();
        $this->postJson('/api/v1/employees/absences', $this->payload(['type' => 'permesso', 'all_day' => false, 'time_from' => '10:00', 'time_to' => '11:00', 'end_date' => '2026-11-02']))->assertCreated();
        $this->postJson('/api/v1/employees/absences', $this->payload())->assertUnprocessable();
    }

    public function test_sickness_can_be_reported_without_protocol_and_updated_later_with_encrypted_storage(): void
    {
        $this->actingAs($this->employee, 'api');
        $id = $this->postJson('/api/v1/employees/absences', $this->payload(['type' => 'malattia']))->assertCreated()->assertJsonPath('data.status', 'reported')->json('data.id');
        $this->patchJson('/api/v1/employees/absences/'.$id, ['certificate_number' => '1234567890', 'note' => 'Comunicazione riservata'])->assertOk()->assertJsonPath('data.certificate_number', '1234567890');
        $stored = DB::table('employee_absences')->find($id);
        $this->assertStringNotContainsString('Comunicazione riservata', $stored->note);
        $this->assertStringNotContainsString('1234567890', $stored->certificate_number);
        $this->actingAs($this->admin, 'api')->patchJson('/api/v1/employees/absences/'.$id, ['note' => 'No'])->assertForbidden();
    }

    public function test_managers_review_requests_with_notes_and_cannot_review_their_own(): void
    {
        $absence = EmployeeAbsence::factory()->create(['user_id' => $this->employee->id]);
        $url = '/api/v1/employees/absences/'.$absence->id.'/review';
        $this->actingAs($this->employee, 'api')->postJson($url, ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin, 'api')->postJson($url, ['status' => 'rejected'])->assertUnprocessable();
        $this->postJson($url, ['status' => 'approved', 'review_note' => 'Confermato'])->assertOk()->assertJsonPath('data.reviewed_by', $this->admin->id);
        $this->postJson($url, ['status' => 'rejected', 'review_note' => 'Duplicata'])->assertUnprocessable();
        $own = EmployeeAbsence::factory()->create(['user_id' => $this->superadmin->id]);
        $this->actingAs($this->superadmin, 'api')->postJson('/api/v1/employees/absences/'.$own->id.'/review', ['status' => 'approved'])->assertForbidden();
        $this->getJson('/api/v1/employees')->assertJsonCount(0, 'data.pending');
    }

    public function test_cancel_preserves_history_and_releases_period(): void
    {
        $this->actingAs($this->employee, 'api');
        $id = $this->postJson('/api/v1/employees/absences', $this->payload())->json('data.id');
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/absences/'.$id.'/cancel')->assertForbidden();
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/absences/'.$id.'/cancel')->assertOk();
        $this->postJson('/api/v1/employees/absences/'.$id.'/cancel')->assertUnprocessable();
        $this->patchJson('/api/v1/employees/absences/'.$id, ['note' => 'No'])->assertUnprocessable();
        $this->assertDatabaseHas('employee_absences', ['id' => $id, 'status' => 'cancelled']);
        $this->postJson('/api/v1/employees/absences', $this->payload())->assertCreated();
    }

    public function test_managers_only_see_staff_in_accessible_branches(): void
    {
        $branch = Branch::factory()->create();
        $hiddenBranch = Branch::factory()->create();
        $this->admin->branches()->attach($branch);
        $this->employee->branches()->attach($branch);
        $hidden = $this->worker('employee');
        $hidden->branches()->attach($hiddenBranch);
        $absence = EmployeeAbsence::factory()->create(['user_id' => $hidden->id, 'note' => 'Riservato altra sede']);
        $this->actingAs($this->admin, 'api')->getJson('/api/v1/employees')->assertOk()->assertJsonCount(2, 'data.staff')->assertDontSee('Riservato altra sede');
        $this->getJson('/api/v1/employees?user_id='.$hidden->id)->assertNotFound();
        $this->postJson('/api/v1/employees/absences/'.$absence->id.'/review', ['status' => 'approved'])->assertNotFound();
        $this->getJson('/api/v1/employees?user_id='.$this->employee->id)->assertOk();
        $this->actingAs($this->superadmin, 'api')->getJson('/api/v1/employees?user_id='.$hidden->id)->assertOk();
    }

    public function test_overnight_and_month_boundary_totals_count_only_time_in_selected_month(): void
    {
        AttendanceSession::factory()->create(['user_id' => $this->employee->id, 'started_at' => '2026-09-30 20:00:00', 'ended_at' => '2026-10-01 02:00:00']);
        $this->actingAs($this->employee, 'api')->getJson('/api/v1/employees?month=2026-10')->assertOk()->assertJsonPath('data.worked_minutes', 240);
        $this->getJson('/api/v1/employees?month=2026-09')->assertOk()->assertJsonPath('data.worked_minutes', 120);
    }

    public function test_daylight_saving_absence_day_uses_rome_calendar_boundaries(): void
    {
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/absences', $this->payload(['start_date' => '2026-10-25', 'end_date' => '2026-10-25']))->assertCreated();
        $absence = EmployeeAbsence::firstOrFail();
        $this->assertSame(25, (int) $absence->starts_at->diffInHours($absence->ends_at));
    }

    public function test_approved_absence_blocks_entry_and_recorded_work_blocks_overlapping_absence(): void
    {
        EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'status' => 'approved', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/clock-in')->assertUnprocessable();
        AttendanceSession::factory()->create(['user_id' => $this->employee->id, 'started_at' => '2026-11-02 08:00:00', 'ended_at' => '2026-11-02 09:00:00']);
        $this->postJson('/api/v1/employees/absences', $this->payload())->assertUnprocessable();
    }

    public function test_corrections_are_authorized_validated_and_keep_immutable_history(): void
    {
        $session = AttendanceSession::factory()->create(['user_id' => $this->employee->id, 'started_at' => '2026-10-19 06:00:00', 'ended_at' => '2026-10-19 14:00:00']);
        $payload = ['started_at' => '2026-10-19T09:00', 'ended_at' => '2026-10-19T17:00', 'reason' => 'Ingresso dimenticato'];
        $url = '/api/v1/employees/sessions/'.$session->id;
        $this->actingAs($this->employee, 'api')->patchJson($url, $payload)->assertForbidden();
        $this->actingAs($this->admin, 'api')->patchJson($url, array_replace($payload, ['reason' => '']))->assertUnprocessable();
        $this->patchJson($url, array_replace($payload, ['started_at' => '2027-01-01T09:00', 'ended_at' => null]))->assertUnprocessable();
        $this->patchJson($url, $payload)->assertOk()->assertJsonPath('data.corrections.0.actor_id', $this->admin->id);
        $this->assertSame('2026-10-19T06:00:00.000000Z', $session->fresh()->corrections[0]['before']['started_at']);
        $this->patchJson($url, array_replace($payload, ['ended_at' => '2026-10-19T18:00']))->assertOk()->assertJsonCount(2, 'data.corrections');
        AttendanceSession::factory()->create(['user_id' => $this->employee->id, 'started_at' => '2026-10-19 17:00:00', 'ended_at' => '2026-10-19 18:00:00']);
        $this->patchJson($url, array_replace($payload, ['ended_at' => '2026-10-19T20:00']))->assertUnprocessable();
    }

    public function test_manager_can_insert_a_forgotten_entry_with_required_reason(): void
    {
        $payload = ['user_id' => $this->employee->id, 'started_at' => '2026-10-19T09:00', 'ended_at' => '2026-10-19T18:00', 'reason' => 'Ingresso dimenticato'];
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/sessions', $payload)->assertForbidden();
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/sessions', $payload)->assertCreated()->assertJsonPath('data.corrections.0.before.started_at', null);
        $this->postJson('/api/v1/employees/sessions', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_worker_with_history_cannot_be_deleted_and_should_be_deactivated(): void
    {
        AttendanceSession::factory()->create(['user_id' => $this->employee->id]);
        $this->actingAs($this->admin, 'api')->deleteJson('/api/v1/users/'.$this->employee->id)->assertUnprocessable();
        $this->assertModelExists($this->employee);
    }

    public function test_pending_leave_does_not_prevent_working_before_approval(): void
    {
        EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/clock-in')->assertCreated();
    }

    public function test_leave_cannot_be_approved_after_employee_worked_in_requested_period(): void
    {
        $absence = EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'starts_at' => '2026-10-19 07:00:00', 'ends_at' => '2026-10-19 15:00:00']);
        AttendanceSession::factory()->create(['user_id' => $this->employee->id, 'started_at' => '2026-10-19 08:00:00', 'ended_at' => '2026-10-19 10:00:00']);
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/absences/'.$absence->id.'/review', ['status' => 'approved'])->assertUnprocessable();
        $this->assertSame('pending', $absence->fresh()->status);
    }

    public function test_rejected_absence_keeps_reason_and_period_can_be_requested_again(): void
    {
        $absence = EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'starts_at' => '2026-11-02 23:00:00', 'ends_at' => '2026-11-03 23:00:00']);
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/absences/'.$absence->id.'/review', ['status' => 'rejected', 'review_note' => 'Periodo da concordare'])->assertOk()->assertJsonPath('data.review_note', 'Periodo da concordare');
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/absences', $this->payload())->assertCreated();
    }

    public function test_api_retry_with_operation_id_does_not_duplicate_a_punch(): void
    {
        $headers = ['X-Operation-Id' => '9a70558b-7141-4eb9-8443-d950af155dd6'];
        $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/clock-in', [], $headers)->assertCreated();
        $this->postJson('/api/v1/employees/clock-in', [], $headers)->assertCreated();
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_client_role_never_gains_attendance_access_through_an_additional_staff_role(): void
    {
        $this->client->assignRole('admin');
        $this->actingAs($this->client, 'api')->getJson('/api/v1/employees')->assertForbidden();
        $this->actingAs($this->superadmin, 'api')->getJson('/api/v1/employees')->assertOk()->assertJsonCount(3, 'data.staff');
    }
}
