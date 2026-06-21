<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->transformNotification($notification));

        return response()->json(['data' => $notifications]);
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' => auth()->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(string $notification): JsonResponse
    {
        $record = $this->findUserNotification($notification);
        $record->markAsRead();

        return response()->json([
            'message' => 'Notifica segnata come letta.',
            'data' => $this->transformNotification($record->fresh()),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Notifiche segnate come lette.']);
    }

    private function findUserNotification(string $notificationId): DatabaseNotification
    {
        return auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }

    private function transformNotification(DatabaseNotification $notification): array
    {
        $title = data_get($notification->data, 'title')
            ?? $this->defaultTitle($notification);

        $body = data_get($notification->data, 'body')
            ?? $this->defaultBody($notification);

        return [
            'id' => $notification->id,
            'title' => $title,
            'body' => $body,
            'read_at' => optional($notification->read_at)?->toIso8601String(),
            'created_at' => optional($notification->created_at)?->toIso8601String(),
        ];
    }

    private function defaultTitle(DatabaseNotification $notification): string
    {
        if ($notification->type === 'App\\Notifications\\DeadlineReminderNotification') {
            return 'Promemoria scadenza';
        }

        return 'Nuova notifica';
    }

    private function defaultBody(DatabaseNotification $notification): string
    {
        if ($notification->type === 'App\\Notifications\\DeadlineReminderNotification') {
            $deadlineAt = data_get($notification->data, 'deadline_at');
            $formatted = $deadlineAt ? Carbon::parse($deadlineAt)->format('d/m H:i') : null;
            $title = data_get($notification->data, 'title', 'Scadenza');

            return $formatted
                ? sprintf('%s entro il %s', $title, $formatted)
                : (string) $title;
        }

        return 'Hai ricevuto una nuova notifica.';
    }
}