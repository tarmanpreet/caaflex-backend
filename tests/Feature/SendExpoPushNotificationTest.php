<?php

namespace Tests\Feature;

use App\Jobs\SendExpoPushNotification;
use App\Models\Appointment;
use App\Models\ExpoPushToken;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Services\NotificationManager;
use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendExpoPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_push_is_cancelled_when_section_is_disabled_before_processing(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        UserNotificationPreference::factory()->create([
            'user_id' => $user->id,
            'section' => 'appointments',
            'enabled' => false,
        ]);

        $job->handle(app(NotificationPreferenceService::class));

        Http::assertNothingSent();
    }

    public function test_queued_push_is_cancelled_when_realtime_is_disabled_before_processing(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        UserNotificationPreference::factory()->create([
            'user_id' => $user->id,
            'section' => 'appointments',
            'enabled' => true,
            'realtime_enabled' => false,
        ]);

        $job->handle(app(NotificationPreferenceService::class));

        Http::assertNothingSent();
    }

    public function test_queued_push_is_cancelled_when_user_is_deactivated(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        $user->update(['is_active' => false]);

        $job->handle(app(NotificationPreferenceService::class));

        Http::assertNothingSent();
    }

    public function test_queued_push_is_cancelled_after_device_is_revoked(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        $user->expoPushTokens()->delete();

        $job->handle(app(NotificationPreferenceService::class));

        Http::assertNothingSent();
    }

    public function test_enabled_push_is_sent_with_mobile_target_and_invalid_device_is_removed(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        $device = $user->expoPushTokens()->firstOrFail();

        $job->handle(app(NotificationPreferenceService::class));

        Http::assertSent(fn ($request): bool => $request[0]['to'] === 'ExpoPushToken[queued-device]'
            && $request[0]['data']['target'] === $job->target);
        Http::assertSentCount(1);
        $this->assertModelMissing($device);
    }

    public function test_legacy_push_without_section_is_safely_suppressed(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $job = $this->queuedPush($user);
        $legacyJob = new SendExpoPushNotification($user->id, $job->title, $job->body, $job->target);

        $legacyJob->handle(app(NotificationPreferenceService::class));

        Http::assertNothingSent();
    }

    private function queuedPush(User $user): SendExpoPushNotification
    {
        $appointment = Appointment::factory()->create();
        ExpoPushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExpoPushToken[queued-device]',
            'token_hash' => hash('sha256', 'ExpoPushToken[queued-device]'),
            'device_id' => 'queued-device',
            'platform' => 'android',
            'last_registered_at' => now(),
        ]);
        Queue::fake();
        Notification::fake();
        Http::preventStrayRequests();
        Http::fake([
            config('services.expo.push_url') => Http::response([
                'data' => [['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]],
            ]),
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

        Queue::assertPushed(SendExpoPushNotification::class);

        return Queue::pushed(SendExpoPushNotification::class)->first();
    }
}
