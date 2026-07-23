<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ScheduledNotificationOccurrence;
use App\Models\User;
use App\Notifications\DomainNotification;
use App\Services\ReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_creates_and_dispatches_one_idempotent_appointment_reminder(): void
    {
        Carbon::setTestNow('2026-07-22 10:00:00');
        $user = User::factory()->create(['is_active' => true]);
        $appointment = Appointment::factory()->create([
            'assigned_user_id' => $user->id,
            'scheduled_at' => now()->addMinutes(61),
            'status' => 'confermato',
        ]);
        Notification::fake();
        $scheduler = app(ReminderScheduler::class);

        $scheduler->synchronize();
        $scheduler->synchronize();

        $this->assertSame(1, ScheduledNotificationOccurrence::query()->count());

        Carbon::setTestNow(now()->addMinute());
        $this->assertSame(1, $scheduler->dispatchDue());

        Notification::assertSentTo($user, DomainNotification::class, fn (DomainNotification $notification): bool => $notification->eventKey === 'appointments.reminder');
        $this->assertDatabaseHas('scheduled_notification_occurrences', [
            'subject_id' => $appointment->id,
            'status' => ScheduledNotificationOccurrence::STATUS_DISPATCHED,
        ]);
    }

    public function test_rescheduled_appointment_invalidates_old_occurrence(): void
    {
        Carbon::setTestNow('2026-07-22 10:00:00');
        $user = User::factory()->create(['is_active' => true]);
        $appointment = Appointment::factory()->create([
            'assigned_user_id' => $user->id,
            'scheduled_at' => now()->addMinutes(61),
            'status' => 'confermato',
        ]);
        Notification::fake();
        $scheduler = app(ReminderScheduler::class);
        $scheduler->synchronize();

        $appointment->update(['scheduled_at' => now()->addDay()]);
        Carbon::setTestNow(now()->addMinute());

        $this->assertSame(0, $scheduler->dispatchDue());
        Notification::assertNothingSent();
        $this->assertDatabaseHas('scheduled_notification_occurrences', [
            'status' => ScheduledNotificationOccurrence::STATUS_CANCELLED,
        ]);
    }
}
