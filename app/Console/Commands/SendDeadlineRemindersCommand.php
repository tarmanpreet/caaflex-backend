<?php

namespace App\Console\Commands;

use App\Jobs\SendDeadlineReminders;
use Illuminate\Console\Command;

class SendDeadlineRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadline:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send deadline reminder notifications for upcoming deadlines';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching deadline reminders job...');

        SendDeadlineReminders::dispatch();

        $this->info('Deadline reminders job dispatched successfully.');

        return self::SUCCESS;
    }
}