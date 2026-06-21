<?php

namespace App\Http\Controllers;

use App\Models\AutoConfirmSlot;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutoConfirmSlotController extends Controller
{
    public function index()
    {
        if (! request()->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        return Inertia::render('AutoConfirmSlots/Index', [
            'slots' => AutoConfirmSlot::orderBy('day_of_week')->get(),
            'days' => AutoConfirmSlot::DAYS,
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
        ]);

        AutoConfirmSlot::create($request->only(['day_of_week', 'time_from', 'time_to']));

        return redirect()->route('auto-confirm-slots.index')
            ->with('success', 'Slot auto-conferma creato.');
    }

    public function destroy(AutoConfirmSlot $slot)
    {
        if (! request()->user()->hasPermissionTo('auto-confirm-slots.manage')) {
            abort(403);
        }

        $slot->delete();

        return redirect()->route('auto-confirm-slots.index')
            ->with('success', 'Slot auto-conferma eliminato.');
    }
}
