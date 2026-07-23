<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_default_notification_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('notification-settings.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('NotificationSettings/Index')
            ->where('sections.appointments.enabled', true)
            ->where('sections.appointments.mail_enabled', true)
            ->where('sections.practices.mail_enabled', false)
            ->where('sections.deadlines.reminder_offsets', [1440, 60]));
    }

    public function test_user_can_update_channels_and_reminder_offsets(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $payload = $this->settingsPayload();
        $payload['sections']['appointments']['mail_enabled'] = false;
        $payload['sections']['appointments']['reminder_offsets'] = [10080, 0];

        $this->actingAs($user)
            ->put(route('notification-settings.update'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'section' => 'appointments',
            'mail_enabled' => false,
            'realtime_enabled' => true,
        ]);
        $this->assertDatabaseHas('user_notification_reminder_preferences', [
            'user_id' => $user->id,
            'section' => 'appointments',
            'minutes_before' => 10080,
        ]);
        $this->assertSame(
            [10080, 0],
            app(NotificationPreferenceService::class)->reminderOffsets($user->fresh(), 'appointments')
        );
    }

    public function test_unknown_sections_and_reminder_offsets_are_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $payload = $this->settingsPayload();
        $payload['sections']['unknown'] = $payload['sections']['appointments'];
        $payload['sections']['appointments']['reminder_offsets'] = [999];

        $this->actingAs($user)
            ->put(route('notification-settings.update'), $payload)
            ->assertSessionHasErrors(['sections', 'sections.appointments.reminder_offsets.0']);
    }

    /** @return array<string, mixed> */
    private function settingsPayload(): array
    {
        return ['sections' => [
            'appointments' => [
                'enabled' => true,
                'mail_enabled' => true,
                'realtime_enabled' => true,
                'reminder_offsets' => [1440, 60],
            ],
            'practices' => [
                'enabled' => true,
                'mail_enabled' => false,
                'realtime_enabled' => true,
            ],
            'deadlines' => [
                'enabled' => true,
                'mail_enabled' => true,
                'realtime_enabled' => true,
                'reminder_offsets' => [1440, 60],
            ],
        ]];
    }
}
