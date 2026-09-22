<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $code,
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['mail' => config('queue.default')];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = method_exists($notifiable, 'name') ? $notifiable->name : '';

        return (new MailMessage)
            ->subject(config('app.name').' — Codice per il reset della password')
            ->greeting("Ciao$name,")
            ->line('Hai richiesto il reset della password per il tuo account.')
            ->line('Inserisci il seguente codice nell\'app entro 60 minuti:')
            ->line($this->code)
            ->line('Se non hai effettuato tu la richiesta, puoi ignorare questa email.');
    }
}
