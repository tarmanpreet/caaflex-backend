<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientProfile;
use App\Models\EmployeeAbsence;
use App\Models\PracticeType;
use App\Models\User;
use App\Notifications\DomainNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    private User $referent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();
        $this->admin = $this->person('admin');
        $this->employee = $this->person('employee');
        $this->referent = $this->person('employee');
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(8, 0));
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);

        return $u;
    }

    private function absence(): EmployeeAbsence
    {
        return EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'starts_at' => '2026-10-28 08:00:00', 'ends_at' => '2026-10-28 12:00:00', 'all_day' => false]);
    }

    private function appointment(string $start = '2026-10-28 09:00:00'): Appointment
    {
        return Appointment::factory()->create(['assigned_user_id' => $this->employee->id, 'scheduled_at' => $start, 'duration_minutes' => 60, 'status' => 'confermato']);
    }

    private function configure(array $ids): void
    {
        $this->actingAs($this->admin, 'api')->putJson('/api/v1/employees/users/'.$this->employee->id.'/approvers', ['approver_ids' => $ids])->assertOk();
    }

    public function test_multiple_referents_and_admin_always_receive_requests_and_can_review(): void
    {
        $second = $this->person('employee');
        $this->configure([$this->referent->id, $second->id]);
        $id = $this->actingAs($this->employee, 'api')->postJson('/api/v1/employees/absences', ['type' => 'ferie', 'start_date' => '2026-10-28', 'end_date' => '2026-10-28', 'all_day' => true])->assertCreated()->json('data.id');
        foreach ([$this->admin, $this->referent, $second] as $person) {
            Notification::assertSentTo($person, DomainNotification::class, fn ($n) => $n->eventKey === 'employees.requested' && ! str_contains(json_encode($n->payload), 'certificate_number'));
        }
        $this->actingAs($this->referent, 'api')->getJson('/api/v1/employees')->assertOk()->assertJsonPath('data.can_manage', false)->assertJsonPath('data.can_review', true)->assertJsonCount(1, 'data.pending');
        $this->postJson('/api/v1/employees/absences/'.$id.'/review', ['status' => 'approved'])->assertOk();
        $this->actingAs($this->admin, 'api')->getJson('/api/v1/employees?user_id='.$this->employee->id)->assertJsonPath('data.absences.0.status', 'approved');
    }

    public function test_default_admin_and_removing_referents_never_removes_admin_authority(): void
    {
        $this->configure([$this->referent->id]);
        $this->configure([]);
        $a = $this->absence();
        $this->actingAs($this->referent, 'api')->getJson('/api/v1/employees/absences/'.$a->id.'/review-context')->assertNotFound();
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/absences/'.$a->id.'/review', ['status' => 'approved'])->assertOk();
    }

    public function test_approver_configuration_rejects_self_clients_inactive_and_admin_targets(): void
    {
        $client = $this->person('cliente');
        $inactive = $this->person('employee');
        $inactive->update(['is_active' => false]);
        foreach ([[$this->employee->id], [$client->id], [$inactive->id]] as $ids) {
            $this->actingAs($this->admin, 'api')->putJson('/api/v1/employees/users/'.$this->employee->id.'/approvers', ['approver_ids' => $ids])->assertUnprocessable();
        }
        $this->putJson('/api/v1/employees/users/'.$this->admin->id.'/approvers', ['approver_ids' => [$this->referent->id]])->assertForbidden();
        $this->actingAs($this->referent, 'api')->putJson('/api/v1/employees/users/'.$this->employee->id.'/approvers', ['approver_ids' => []])->assertForbidden();
    }

    public function test_approval_requires_reassignment_and_can_assign_all_appointments_with_one_select(): void
    {
        $a = $this->absence();
        $one = $this->appointment();
        $two = $this->appointment('2026-10-28 11:00:00');
        $url = '/api/v1/employees/absences/'.$a->id;
        $this->actingAs($this->admin, 'api')->getJson($url.'/review-context')->assertOk()->assertJsonCount(2, 'data.appointments');
        $this->postJson($url.'/review', ['status' => 'approved'])->assertUnprocessable();
        $this->assertSame('pending', $a->fresh()->status);
        $this->postJson($url.'/review', ['status' => 'approved', 'replacement_user_id' => $this->admin->id])->assertOk();
        $this->assertSame($this->admin->id, $one->fresh()->assigned_user_id);
        $this->assertSame($this->admin->id, $two->fresh()->assigned_user_id);
    }

    public function test_individual_assignments_are_atomic_when_one_replacement_is_unavailable(): void
    {
        $a = $this->absence();
        $one = $this->appointment();
        $two = $this->appointment('2026-10-28 11:00:00');
        $other = $this->person('admin');
        Appointment::factory()->create(['assigned_user_id' => $other->id, 'scheduled_at' => '2026-10-28 11:00:00', 'duration_minutes' => 60, 'status' => 'confermato']);
        $payload = ['status' => 'approved', 'assignments' => [['appointment_id' => $one->id, 'user_id' => $this->admin->id], ['appointment_id' => $two->id, 'user_id' => $other->id]]];
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/employees/absences/'.$a->id.'/review', $payload)->assertUnprocessable();
        $this->assertSame($this->employee->id, $one->fresh()->assigned_user_id);
        $this->assertSame('pending', $a->fresh()->status);
        $payload['assignments'][1]['user_id'] = $this->admin->id;
        $this->postJson('/api/v1/employees/absences/'.$a->id.'/review', $payload)->assertOk();
    }

    public function test_approval_catches_appointments_starting_before_absence_and_rejects_foreign_ids(): void
    {
        $a = $this->absence();
        $before = $this->appointment('2026-10-28 07:30:00');
        $adjacent = $this->appointment('2026-10-28 12:00:00');
        $this->actingAs($this->admin, 'api')->getJson('/api/v1/employees/absences/'.$a->id.'/review-context')->assertJsonCount(1, 'data.appointments')->assertJsonPath('data.appointments.0.id', $before->id);
        $this->postJson('/api/v1/employees/absences/'.$a->id.'/review', ['status' => 'approved', 'replacement_user_id' => $this->admin->id, 'assignments' => [['appointment_id' => $adjacent->id, 'user_id' => $this->admin->id]]])->assertUnprocessable();
    }

    public function test_reported_and_approved_absences_block_booking_and_are_private_calendar_events(): void
    {
        $a = $this->absence();
        $a->update(['status' => 'approved', 'note' => 'HEALTH-PRIVATE', 'certificate_number' => 'SECRET-CERT']);
        $this->actingAs($this->admin, 'api')->postJson('/api/v1/appointments', ['client_profile_id' => ClientProfile::factory()->create()->id, 'practice_type_id' => PracticeType::factory()->create()->id, 'assigned_user_id' => $this->employee->id, 'scheduled_at' => '2026-10-28 09:00:00', 'duration_minutes' => 30])->assertUnprocessable();
        $response = $this->getJson('/api/v1/appointments-calendar?from=2026-10-01&to=2026-10-31')->assertOk()->assertDontSee('HEALTH-PRIVATE')->assertDontSee('SECRET-CERT');
        $event = collect($response->json('data'))->firstWhere('id', 'absence-'.$a->id);
        $this->assertFalse($event['editable']);
        $this->actingAs($this->referent, 'api')->getJson('/api/v1/appointments-calendar?from=2026-10-01&to=2026-10-31')->assertDontSee('absence-'.$a->id);
    }

    public function test_summary_and_daily_export_include_absences_and_prevent_formula_injection(): void
    {
        $this->employee->update(['name' => '=HYPERLINK("example")']);
        EmployeeAbsence::factory()->create(['user_id' => $this->employee->id, 'type' => 'ferie', 'status' => 'approved', 'starts_at' => '2026-10-27 23:00:00', 'ends_at' => '2026-10-29 23:00:00']);
        $this->actingAs($this->admin, 'api');
        $data = $this->getJson('/api/v1/employees?month=2026-10')->assertOk()->json('data.summary');
        $person = collect($data)->firstWhere('id', $this->employee->id);
        $this->assertSame(2, $person['absence_days']);
        $csv = $this->getJson('/api/v1/employees/export?month=2026-10&user_id='.$this->employee->id)->assertOk()->json('data.content');
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Ferie (confermata)', $csv);
        $this->assertStringContainsString('31/10/2026', $csv);
        $this->actingAs($this->referent, 'api')->getJson('/api/v1/employees/export?month=2026-10&user_id='.$this->employee->id)->assertForbidden();
        $this->getJson('/api/v1/employees/export?month=2026-10')->assertOk();
    }

    public function test_former_operations_page_redirects_to_practices(): void
    {
        $this->actingAs($this->admin, 'web')->get('/operations')->assertRedirect(route('practices.index'));
    }

    public function test_replacement_select_excludes_conflicts_after_the_end_of_the_absence(): void
    {
        $absence = $this->absence();
        $appointment = $this->appointment('2026-10-28 11:30:00');
        Appointment::factory()->create(['assigned_user_id' => $this->admin->id, 'scheduled_at' => '2026-10-28 12:00:00', 'duration_minutes' => 30, 'status' => 'confermato']);
        $context = $this->actingAs($this->admin, 'api')->getJson('/api/v1/employees/absences/'.$absence->id.'/review-context')->assertOk()->json('data');
        $this->assertSame($appointment->id, $context['appointments'][0]['id']);
        $this->assertNotContains($this->admin->id, $context['appointments'][0]['eligible_user_ids']);
    }
}
