<?php

namespace App\Services;

use App\Jobs\SendExpoPushNotification;
use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NotificationManager
{
    public function __construct(private NotificationPreferenceService $preferences) {}

    /**
     * @param  iterable<int, User>  $recipients
     */
    public function send(
        iterable $recipients,
        string $eventKey,
        string $section,
        string $title,
        string $body,
        Model $subject,
        string $actionUrl,
        ?int $actorId = null,
        string $severity = 'info',
        ?Carbon $occurredAt = null,
    ): void {
        Collection::make($recipients)
            ->filter(fn (User $user): bool => $user->is_active && $user->id !== $actorId)
            ->unique('id')
            ->each(function (User $user) use ($eventKey, $section, $title, $body, $subject, $actionUrl, $severity, $occurredAt): void {
                $channels = $this->preferences->channels($user, $section);

                if ($channels === []) {
                    return;
                }

                $user->notify(new DomainNotification($eventKey, [
                    'event_key' => $eventKey,
                    'section' => $section,
                    'severity' => $severity,
                    'title' => $title,
                    'body' => $body,
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'target' => $this->target($subject, $section),
                    'action_url' => $actionUrl,
                    'occurred_at' => ($occurredAt ?? now())->toIso8601String(),
                ], $channels));

                if (in_array('broadcast', $channels, true) && $user->expoPushTokens()->exists()) {
                    SendExpoPushNotification::dispatch(
                        $user->id,
                        $title,
                        $body,
                        $this->target($subject, $section),
                    );
                }
            });
    }

    /** @return array<string, mixed> */
    private function target(Model $subject, string $section): array
    {
        return match ($subject->getMorphClass()) {
            \App\Models\Appointment::class => [
                'resource' => 'appointment',
                'id' => $subject->getKey(),
            ],
            \App\Models\Practice::class => [
                'resource' => 'practice',
                'id' => $subject->getKey(),
            ],
            \App\Models\PracticeDeadline::class => [
                'resource' => 'practice',
                'id' => $subject->getAttribute('practice_id'),
                'section' => 'steps',
                'child_resource' => 'practice_deadline',
                'child_id' => $subject->getKey(),
            ],
            default => [
                'resource' => $section,
                'id' => $subject->getKey(),
            ],
        };
    }
}
