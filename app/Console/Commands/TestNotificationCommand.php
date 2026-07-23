<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:test
    {user : ID o indirizzo email dell’utente destinatario}
    {--mail : Testa il canale email; senza opzioni vengono testati entrambi i trasporti}
    {--websocket : Testa il canale WebSocket; senza opzioni vengono testati entrambi i trasporti}')]
#[Description('Accoda una notifica diagnostica ignorando le preferenze personali del destinatario')]
class TestNotificationCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = (string) $this->argument('user');
        $user = $this->findUser($identifier);

        if (! $user) {
            $this->error("Nessun utente trovato per: {$identifier}");

            return self::FAILURE;
        }

        if (! $user->is_active) {
            $this->error("L'utente {$user->email} non è attivo.");

            return self::FAILURE;
        }

        $mailRequested = (bool) $this->option('mail');
        $websocketRequested = (bool) $this->option('websocket');
        $testBothChannels = ! $mailRequested && ! $websocketRequested;
        $channels = ['database'];
        $channelLabels = ['pannello'];

        if ($mailRequested || $testBothChannels) {
            $channels[] = 'mail';
            $channelLabels[] = 'email';
        }

        if ($websocketRequested || $testBothChannels) {
            $channels[] = 'broadcast';
            $channelLabels[] = 'WebSocket';
        }

        $user->notify(new DomainNotification(
            eventKey: 'system.test',
            payload: [
                'event_key' => 'system.test',
                'section' => 'appointments',
                'severity' => 'info',
                'title' => 'Notifica di test',
                'body' => 'Questa notifica verifica il corretto funzionamento dei canali configurati.',
                'subject_type' => $user->getMorphClass(),
                'subject_id' => $user->getKey(),
                'action_url' => route('notifications.index', absolute: false),
                'occurred_at' => now()->toIso8601String(),
            ],
            channels: $channels,
        ));

        $this->info('Notifica di test accodata per '.$user->email.'.');
        $this->line('Canali: '.implode(', ', $channelLabels).'.');

        return self::SUCCESS;
    }

    private function findUser(string $identifier): ?User
    {
        if (ctype_digit($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('email', $identifier)->first();
    }
}
