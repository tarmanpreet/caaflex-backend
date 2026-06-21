<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AutoConfirmSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutoConfirmSlotController extends Controller
{
    public function index(): JsonResponse
    {
        if (! request()->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        return response()->json([
            'data' => AutoConfirmSlot::orderBy('day_of_week')->get(),
            'days' => AutoConfirmSlot::DAYS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
        ]);

        $slot = AutoConfirmSlot::create($request->only(['day_of_week', 'time_from', 'time_to']));

        return response()->json([
            'message' => 'Slot auto-conferma creato.',
            'data' => $slot,
        ], 201);
    }

    public function destroy(Request $request, AutoConfirmSlot $slot): JsonResponse
    {
        if (! $request->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        $slot->delete();

        return response()->json([
            'message' => 'Slot auto-conferma eliminato.',
        ]);
    }
}
