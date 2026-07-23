<?php

namespace App\Console\Commands;

use App\Services\ReminderScheduler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:dispatch-reminders')]
#[Description('Sincronizza e invia i promemoria di appuntamenti e scadenze')]
class DispatchScheduledNotificationsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReminderScheduler $scheduler): int
    {
        $scheduler->synchronize();
        $count = $scheduler->dispatchDue();

        $this->info("Promemoria accodati: {$count}");

        return self::SUCCESS;
    }
}
