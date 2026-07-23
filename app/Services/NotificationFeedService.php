<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class NotificationFeedService
{
    public function paginate(User $user, ?string $section = null, ?bool $unread = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        if ($section) {
            $query->where('data->section', $section);
        }

        if ($unread === true) {
            $query->whereNull('read_at');
        } elseif ($unread === false) {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage)->through(fn (DatabaseNotification $notification): array => $this->transform($notification));
    }

    /** @return array<string, mixed> */
    public function transform(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $legacyDeadlineAt = data_get($data, 'deadline_at');
        $legacyTitle = data_get($data, 'title', 'Scadenza');

        return [
            'id' => $notification->id,
            'event_key' => data_get($data, 'event_key', $notification->type),
            'section' => data_get($data, 'section', 'deadlines'),
            'severity' => data_get($data, 'severity', 'info'),
            'title' => data_get($data, 'title', 'Nuova notifica'),
            'body' => data_get($data, 'body', $legacyDeadlineAt
                ? sprintf('%s entro il %s', $legacyTitle, Carbon::parse($legacyDeadlineAt)->format('d/m/Y H:i'))
                : 'Hai ricevuto una nuova notifica.'),
            'action_url' => data_get($data, 'action_url', data_get($data, 'practice_id')
                ? route('practices.show', data_get($data, 'practice_id'), false).'#deadlines'
                : route('dashboard', absolute: false)),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
