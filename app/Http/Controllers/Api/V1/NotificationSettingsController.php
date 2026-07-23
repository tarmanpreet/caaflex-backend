<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;

class NotificationSettingsController extends Controller
{
    public function show(NotificationPreferenceService $preferences): JsonResponse
    {
        return response()->json(['data' => [
            'sections' => $preferences->settings(request()->user()),
            'reminder_options' => config('notifications.reminder_options'),
        ]]);
    }

    public function update(UpdateNotificationSettingsRequest $request, NotificationPreferenceService $preferences): JsonResponse
    {
        $preferences->update($request->user(), $request->validated('sections'));

        return response()->json([
            'message' => 'Impostazioni notifiche aggiornate.',
            'data' => ['sections' => $preferences->settings($request->user())],
        ]);
    }
}
