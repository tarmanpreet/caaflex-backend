<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListNotificationsRequest;
use App\Services\NotificationFeedService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request, NotificationFeedService $feed): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $feed->paginate($request->user(), $request->validated('section'), $request->unreadFilter(), 20),
            'filters' => $request->only(['section', 'status']),
            'sections' => collect(config('notifications.sections'))->map(fn (array $section): string => $section['label']),
        ]);
    }

    public function feed(ListNotificationsRequest $request, NotificationFeedService $feed): JsonResponse
    {
        return response()->json($feed->paginate(
            $request->user(),
            $request->validated('section'),
            $request->unreadFilter(),
            (int) ($request->validated('per_page') ?? 10),
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

        return response()->json(['data' => $feed->transform($record->fresh())]);
    }

    public function markAllAsRead(): JsonResponse
    {
        request()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifiche segnate come lette.']);
    }

    public function open(string $notification, NotificationFeedService $feed): JsonResponse
    {
        $record = request()->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $url = $feed->transform($record)['action_url'];

        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = route('dashboard', absolute: false);
        }

        return response()->json(['data' => ['redirect_url' => $url]]);
    }
}
