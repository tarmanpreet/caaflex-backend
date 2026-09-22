<?php

namespace Tests\Feature;

use App\Jobs\SendExpoPushNotification;
use App\Models\Appointment;
use App\Models\ExpoPushToken;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\DomainNotification;
use App\Services\NotificationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_resolves_database_mail_and_broadcast_channels(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $appointment = Appointment::factory()->create();
        Notification::fake();

        app(NotificationManager::class)->send(
            [$user],
            'appointments.status_changed',
            'appointments',
            'Stato aggiornato',
            'Il nuovo stato è confermato.',
            $appointment,
            route('appointments.show', $appointment, false),
        );

        Notification::assertSentTo($user, DomainNotification::class, function (DomainNotification $notification) use ($appointment): bool {
            return $notification->channels === ['database', 'mail', 'broadcast']
                && $notification->payload['section'] === 'appointments'
                && $notification->payload['target'] === [
                    'resource' => 'appointment',
                    'id' => $appointment->id,
                ]
                && $notification->payload['action_url'] !== null;
        });
    }

    public function test_disabled_section_suppresses_all_delivery_channels(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $appointment = Appointment::factory()->create();
        UserNotificationPreference::factory()->create([
            'user_id' => $user->id,
            'section' => 'appointments',
            'enabled' => false,
        ]);
        Notification::fake();

        app(NotificationManager::class)->send(
            [$user],
            'appointments.status_changed',
            'appointments',
            'Stato aggiornato',
            'Corpo',
            $appointment,
            '/appointments/'.$appointment->id,
        );

        Notification::assertNothingSent();
    }

    public function test_actor_and_inactive_users_are_excluded(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $inactive = User::factory()->create(['is_active' => false]);
        $appointment = Appointment::factory()->create();
        Notification::fake();

        app(NotificationManager::class)->send(
            [$actor, $inactive],
            'appointments.status_changed',
            'appointments',
            'Stato aggiornato',
            'Corpo',
            $appointment,
            '/appointments/'.$appointment->id,
            $actor->id,
        );

        Notification::assertNothingSent();
    }

    public function test_realtime_preference_dispatches_push_for_registered_device(): void
    {
        Queue::fake();
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);
        $appointment = Appointment::factory()->create();
        ExpoPushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExpoPushToken[test-device]',
            'token_hash' => hash('sha256', 'ExpoPushToken[test-device]'),
            'device_id' => 'device-test',
            'platform' => 'ios',
            'last_registered_at' => now(),
        ]);

        app(NotificationManager::class)->send(
            [$user],
            'appointments.status_changed',
            'appointments',
            'Stato aggiornato',
            'Corpo',
            $appointment,
            '/appointments/'.$appointment->id,
        );

        Queue::assertPushed(SendExpoPushNotification::class, fn (SendExpoPushNotification $job): bool => $job->userId === $user->id
            && $job->target === ['resource' => 'appointment', 'id' => $appointment->id]
        );
    }
}
