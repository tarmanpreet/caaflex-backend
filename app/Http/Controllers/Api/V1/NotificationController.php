<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListNotificationsRequest;
use App\Services\NotificationFeedService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request, NotificationFeedService $feed): JsonResponse
    {
        return response()->json($feed->paginate(
            $request->user(),
            $request->validated('section'),
            $request->unreadFilter(),
            (int) ($request->validated('per_page') ?? 20),
        ));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json(['data' => ['unread_count' => request()->user()->unreadNotifications()->count()]]);
    }

    public function markAsRead(string $notification, NotificationFeedService $feed): JsonResponse
    {
        $record = request()->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->json([
            'message' => 'Notifica segnata come letta.',
            'data' => $feed->transform($record->fresh()),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        request()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifiche segnate come lette.']);
    }
}
