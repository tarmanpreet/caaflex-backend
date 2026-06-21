<?php

namespace App\Notifications;

use App\Models\PracticeDeadline;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PracticeDeadline $deadline
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'deadline_id' => $this->deadline->id,
            'title' => $this->deadline->title,
            'practice_id' => $this->deadline->practice_id,
            'deadline_at' => $this->deadline->deadline_at->toIso8601String(),
            'priority' => $this->deadline->priority,
        ];
    }
}