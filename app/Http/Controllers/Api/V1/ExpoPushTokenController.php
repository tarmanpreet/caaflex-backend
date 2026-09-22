<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ExpoPushToken\RegisterExpoPushTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpoPushTokenRequest;
use App\Models\ExpoPushToken;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpoPushTokenController extends Controller
{
    public function store(
        StoreExpoPushTokenRequest $request,
        RegisterExpoPushTokenAction $action,
        NotificationPreferenceService $preferences,
    ): JsonResponse {
        $realtimeEnabled = collect($preferences->settings($request->user()))
            ->contains(fn (array $settings): bool => $settings['enabled'] && $settings['realtime_enabled']);

        if (! $realtimeEnabled) {
            return response()->json([
                'message' => 'Le notifiche in tempo reale sono disabilitate nelle preferenze.',
            ], 409);
        }

        $pushToken = $action->execute($request->user(), $request->validated());

        return response()->json([
            'message' => 'Token push registrato.',
            'data' => $pushToken,
        ], $pushToken->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, ExpoPushToken $expoPushToken): JsonResponse
    {
        abort_unless($expoPushToken->user_id === $request->user()->id, 404);

        $expoPushToken->delete();

        return response()->json([
            'message' => 'Token push revocato.',
        ]);
    }
}
