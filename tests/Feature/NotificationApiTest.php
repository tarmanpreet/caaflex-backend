<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_user_can_list_notifications_and_unread_count(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => Practice::factory()->create()->id,
            'title' => 'Nuovo appuntamento assegnato',
            'deadline_at' => now()->addDay(),
        ]);

        $this->admin->notify(new DeadlineReminderNotification($deadline));

        $listResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/notifications');

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Nuovo appuntamento assegnato');

        $countResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/notifications/unread-count');

        $countResponse->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $deadline = PracticeDeadline::factory()->create([
            'practice_id' => Practice::factory()->create()->id,
            'deadline_at' => now()->addDay(),
        ]);

        $this->admin->notify(new DeadlineReminderNotification($deadline));
        $notificationId = $this->admin->notifications()->first()->id;

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertOk()
            ->assertJsonPath('message', 'Notifica segnata come letta.');

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $deadlineA = PracticeDeadline::factory()->create([
            'practice_id' => Practice::factory()->create()->id,
            'deadline_at' => now()->addDay(),
        ]);
        $deadlineB = PracticeDeadline::factory()->create([
            'practice_id' => Practice::factory()->create()->id,
            'deadline_at' => now()->addDays(2),
        ]);

        $this->admin->notify(new DeadlineReminderNotification($deadlineA));
        $this->admin->notify(new DeadlineReminderNotification($deadlineB));

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'Notifiche segnate come lette.');

        $this->assertSame(0, $this->admin->fresh()->unreadNotifications()->count());
    }
}
