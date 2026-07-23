<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $eventKey,
        public array $payload,
        public array $channels,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail' => config('queue.default'),
            'broadcast' => config('queue.default'),
        ];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications-mail',
            'broadcast' => 'notifications-broadcast',
        ];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return (bool) $notifiable->is_active && in_array($channel, $this->channels, true);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject((string) $this->payload['title'])
            ->greeting('Ciao '.$notifiable->name.',')
            ->line((string) $this->payload['body']);

        if (! empty($this->payload['action_url'])) {
            $message->action('Apri nel gestionale', url((string) $this->payload['action_url']));
        }

        return $message->line('Puoi modificare queste comunicazioni dalle impostazioni notifiche.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->payload))->onQueue('notifications-broadcast');
    }

    public function databaseType(object $notifiable): string
    {
        return $this->eventKey;
    }

    public function broadcastType(): string
    {
        return $this->eventKey;
    }
}
