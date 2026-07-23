<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\DomainNotification;
use App\Services\NotificationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        Notification::assertSentTo($user, DomainNotification::class, function (DomainNotification $notification): bool {
            return $notification->channels === ['database', 'mail', 'broadcast']
                && $notification->payload['section'] === 'appointments'
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
}
