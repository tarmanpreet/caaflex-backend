<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use App\Notifications\DomainNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_appointment_notification_opens_its_inertia_page(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertNotificationOpens(route('appointments.show', $appointment, false), 'Appointments/Show');
    }

    public function test_practice_notification_preserves_the_steps_fragment(): void
    {
        $practice = Practice::factory()->create();

        $this->assertNotificationOpens(route('practices.show', $practice, false).'#steps', 'Practices/Show');
    }

    public function test_absence_notification_preserves_employee_and_month_filters(): void
    {
        $employee = User::factory()->create(['is_active' => true]);
        $employee->assignRole('employee');
        $url = route('employees.index', ['month' => '2026-10', 'user_id' => $employee->id], false);

        $this->assertNotificationOpens($url, 'Employees/Index');
    }

    public function test_legacy_deadline_notification_opens_the_practice(): void
    {
        $deadline = PracticeDeadline::factory()->create();
        $this->admin->notify(new DeadlineReminderNotification($deadline));
        $notification = $this->admin->notifications()->firstOrFail();

        $this->actingAs($this->admin)
            ->postJson(route('notifications.open', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.redirect_url', route('practices.show', $deadline->practice_id, false).'#steps');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_old_step_reminder_uses_the_current_practice_instead_of_the_saved_url(): void
    {
        $deadline = PracticeDeadline::factory()->create();
        $this->sendStepReminder($deadline, '/practices/999999#deadlines');
        $notification = $this->admin->notifications()->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->postJson(route('notifications.open', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.redirect_url', route('practices.show', $deadline->practice_id, false).'#steps');

        $this->get($response->json('data.redirect_url'))->assertOk();
    }

    public function test_deleted_step_reminder_opens_notifications_with_an_explanation(): void
    {
        $deadline = PracticeDeadline::factory()->create();
        $this->sendStepReminder($deadline, route('practices.show', $deadline->practice_id, false).'#steps');
        $deadline->delete();
        $notification = $this->admin->notifications()->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->postJson(route('notifications.open', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.redirect_url', route('notifications.index', absolute: false))
            ->assertSessionHas('error', 'Lo step o la pratica collegata a questa notifica non è più disponibile.');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->get($response->json('data.redirect_url'))->assertOk();
    }

    public function test_reminder_for_a_deleted_practice_does_not_open_a_404(): void
    {
        $deadline = PracticeDeadline::factory()->create();
        $this->sendStepReminder($deadline, route('practices.show', $deadline->practice_id, false).'#steps');
        $deadline->practice->delete();
        $notification = $this->admin->notifications()->firstOrFail();

        $this->actingAs($this->admin)
            ->postJson(route('notifications.open', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.redirect_url', route('notifications.index', absolute: false))
            ->assertSessionHas('error');
    }

    public function test_existing_reminders_include_step_client_practice_and_local_deadline(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'title' => 'Surrender Passport',
            'deadline_at' => '2026-09-10 17:31:00',
        ]);
        $this->sendStepReminder($deadline, '/practices/999999#deadlines');
        $practice = $deadline->practice;
        $client = $practice->client;

        $response = $this->actingAs($this->admin)->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Promemoria step: Surrender Passport')
            ->assertJsonPath('data.0.action_url', route('practices.show', $practice, false).'#steps');

        $body = $response->json('data.0.body');
        $this->assertStringContainsString('10/09/2026 19:31', $body);
        $this->assertStringContainsString("Pratica #{$practice->id}", $body);
        $this->assertStringContainsString($practice->tracking_code, $body);
        $this->assertStringContainsString($practice->type, $body);
        $this->assertStringContainsString("{$client->first_name} {$client->last_name}", $body);
        $this->assertStringContainsString($deadline->assignee->name, $body);
    }

    public function test_user_cannot_open_another_users_web_notification(): void
    {
        $deadline = PracticeDeadline::factory()->create();
        $this->sendStepReminder($deadline, '/practices/'.$deadline->practice_id.'#steps');
        $notification = $this->admin->notifications()->firstOrFail();
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)->postJson(route('notifications.open', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    private function sendStepReminder(PracticeDeadline $deadline, string $url): void
    {
        $this->admin->notifyNow(new DomainNotification('deadlines.reminder', [
            'event_key' => 'deadlines.reminder',
            'section' => 'deadlines',
            'title' => 'Promemoria step',
            'body' => "Completa «{$deadline->title}».",
            'subject_type' => PracticeDeadline::class,
            'subject_id' => $deadline->id,
            'action_url' => $url,
        ], ['database']));
    }

    private function assertNotificationOpens(string $url, string $component): void
    {
        $this->admin->notifyNow(new DomainNotification('navigation.test', [
            'title' => 'Notifica di prova',
            'action_url' => $url,
        ], ['database']));
        $notification = $this->admin->notifications()->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->postJson(route('notifications.open', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.redirect_url', $url);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->get($response->json('data.redirect_url'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
}
