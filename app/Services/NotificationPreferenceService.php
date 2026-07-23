<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NotificationPreferenceService
{
    /** @return array<string, array<string, mixed>> */
    public function settings(User $user): array
    {
        $user->loadMissing(['notificationPreferences', 'notificationReminderPreferences']);

        return collect(config('notifications.sections'))
            ->mapWithKeys(function (array $definition, string $section) use ($user): array {
                $preference = $user->notificationPreferences->firstWhere('section', $section);
                $defaults = $definition['defaults'];
                $reminderOffsets = [];

                if ($definition['supports_reminders']) {
                    $reminderOffsets = $preference?->reminders_configured
                        ? $user->notificationReminderPreferences
                            ->where('section', $section)
                            ->pluck('minutes_before')
                            ->sortDesc()
                            ->values()
                            ->all()
                        : config('notifications.default_reminder_offsets');
                }

                return [$section => [
                    'key' => $section,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'enabled' => $preference?->enabled ?? $defaults['enabled'],
                    'mail_enabled' => $preference?->mail_enabled ?? $defaults['mail'],
                    'realtime_enabled' => $preference?->realtime_enabled ?? $defaults['realtime'],
                    'supports_reminders' => $definition['supports_reminders'],
                    'reminder_offsets' => $reminderOffsets,
                ]];
            })
            ->all();
    }

    /** @return array<int, string> */
    public function channels(User $user, string $section): array
    {
        $settings = $this->settings($user)[$section] ?? null;

        if (! $settings || ! $settings['enabled']) {
            return [];
        }

        $channels = ['database'];

        if ($settings['mail_enabled']) {
            $channels[] = 'mail';
        }

        if ($settings['realtime_enabled']) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /** @return array<int, int> */
    public function reminderOffsets(User $user, string $section): array
    {
        return $this->settings($user)[$section]['reminder_offsets'] ?? [];
    }

    public function update(User $user, array $sections): void
    {
        DB::transaction(function () use ($user, $sections): void {
            foreach (config('notifications.sections') as $section => $definition) {
                $values = $sections[$section];
                $supportsReminders = (bool) $definition['supports_reminders'];

                UserNotificationPreference::query()->updateOrCreate(
                    ['user_id' => $user->id, 'section' => $section],
                    [
                        'enabled' => $values['enabled'],
                        'mail_enabled' => $values['mail_enabled'],
                        'realtime_enabled' => $values['realtime_enabled'],
                        'reminders_configured' => $supportsReminders,
                    ]
                );

                if ($supportsReminders) {
                    $user->notificationReminderPreferences()->where('section', $section)->delete();

                    $rows = collect(Arr::get($values, 'reminder_offsets', []))
                        ->unique()
                        ->map(fn (int $minutes): array => [
                            'section' => $section,
                            'minutes_before' => $minutes,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->all();

                    if ($rows !== []) {
                        $user->notificationReminderPreferences()->createMany($rows);
                    }
                }
            }
        });

        $user->unsetRelation('notificationPreferences');
        $user->unsetRelation('notificationReminderPreferences');
    }
}
