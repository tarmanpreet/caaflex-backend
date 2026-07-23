<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TestNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tests_mail_and_websocket_by_default(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Notification::fake();

        $this->artisan('notifications:test', ['user' => $user->email])
            ->expectsOutput("Notifica di test accodata per {$user->email}.")
            ->expectsOutput('Canali: pannello, email, WebSocket.')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            DomainNotification::class,
            fn (DomainNotification $notification): bool => $notification->eventKey === 'system.test'
                && $notification->channels === ['database', 'mail', 'broadcast']
                && $notification->payload['action_url'] === '/notifications',
        );
    }

    public function test_it_can_test_only_mail_in_addition_to_the_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Notification::fake();

        $this->artisan('notifications:test', [
            'user' => (string) $user->id,
            '--mail' => true,
        ])->assertSuccessful();

        Notification::assertSentTo(
            $user,
            DomainNotification::class,
            fn (DomainNotification $notification): bool => $notification->channels === ['database', 'mail'],
        );
    }

    public function test_it_can_test_only_websocket_in_addition_to_the_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Notification::fake();

        $this->artisan('notifications:test', [
            'user' => $user->email,
            '--websocket' => true,
        ])->assertSuccessful();

        Notification::assertSentTo(
            $user,
            DomainNotification::class,
            fn (DomainNotification $notification): bool => $notification->channels === ['database', 'broadcast'],
        );
    }

    public function test_it_fails_for_unknown_or_inactive_users(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        Notification::fake();

        $this->artisan('notifications:test', ['user' => 'missing@example.com'])
            ->expectsOutput('Nessun utente trovato per: missing@example.com')
            ->assertFailed();

        $this->artisan('notifications:test', ['user' => $inactiveUser->email])
            ->expectsOutput("L'utente {$inactiveUser->email} non è attivo.")
            ->assertFailed();

        Notification::assertNothingSent();
    }
}
