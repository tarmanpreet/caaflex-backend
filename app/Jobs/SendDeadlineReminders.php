<?php

namespace App\Jobs;

use App\Mail\DeadlineReminderMailable;
use App\Models\DeadlineReminder;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendDeadlineReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();
        
        // Find reminders that haven't been sent yet and are due
        // A reminder is due when: deadline_at - minutes_before <= now
        $reminders = DeadlineReminder::query()
            ->where('sent', false)
            ->whereHas('deadline', function ($query) use ($now) {
                $query->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->with(['deadline.practice', 'deadline.assignee'])
            ->get()
            ->filter(function ($reminder) use ($now) {
                $deadline = $reminder->deadline;
                if (! $deadline) {
                    return false;
                }
                
                $reminderTime = $deadline->deadline_at->copy()->subMinutes($reminder->minutes_before);
                return $reminderTime <= $now && $deadline->deadline_at > $now;
            });

        $processedCount = 0;

        foreach ($reminders as $reminder) {
            $this->sendReminder($reminder);
            $processedCount++;
        }

        Log::info("Processed {$processedCount} deadline reminders.");
    }

    /**
     * Send a single reminder.
     */
    private function sendReminder(DeadlineReminder $reminder): void
    {
        $deadline = $reminder->deadline;
        $assignee = $deadline->assignee;

        if (! $assignee) {
            Log::warning("Deadline {$deadline->id} has no assignee. Skipping reminder {$reminder->id}.");
            $this->markAsSent($reminder);
            return;
        }

        try {
            if ($reminder->type === DeadlineReminder::TYPE_EMAIL) {
                Mail::to($assignee->email)
                    ->send(new DeadlineReminderMailable($deadline));
            } elseif ($reminder->type === DeadlineReminder::TYPE_IN_APP) {
                Notification::send($assignee, new DeadlineReminderNotification($deadline));
            }

            $this->markAsSent($reminder);
        } catch (\Exception $e) {
            Log::error("Failed to send reminder {$reminder->id}: {$e->getMessage()}");
        }
    }

    /**
     * Mark reminder as sent.
     */
    private function markAsSent(DeadlineReminder $reminder): void
    {
        $reminder->update([
            'sent' => true,
            'sent_at' => now(),
        ]);
    }
}