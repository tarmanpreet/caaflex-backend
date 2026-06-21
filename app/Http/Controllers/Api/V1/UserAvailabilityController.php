<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAvailabilityController extends Controller
{
    public function index(User $user): JsonResponse
    {
        if (! request()->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        return response()->json([
            'data' => [
                'targetUser' => $user->only('id', 'name', 'email'),
                'availabilities' => $user->availabilities()->orderBy('day_of_week')->get(),
                'days' => UserAvailability::DAYS,
            ],
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
        ]);

        $availability = UserAvailability::updateOrCreate(
            ['user_id' => $user->id, 'day_of_week' => $request->day_of_week],
            ['time_from' => $request->time_from, 'time_to' => $request->time_to]
        );

        return response()->json([
            'message' => 'Disponibilità salvata.',
            'data' => $availability,
        ], 201);
    }

    public function destroy(Request $request, UserAvailability $availability): JsonResponse
    {
        if (! $request->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        $availability->delete();

        return response()->json([
            'message' => 'Disponibilità eliminata.',
        ]);
    }
}
