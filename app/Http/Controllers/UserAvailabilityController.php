<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserAvailabilityController extends Controller
{
    use AuthorizesRequests;

    public function index(User $user)
    {
        if (! request()->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        return Inertia::render('UserAvailabilities/Index', [
            'targetUser' => $user->only('id', 'name', 'email'),
            'availabilities' => $user->availabilities()->orderBy('day_of_week')->get(),
            'days' => UserAvailability::DAYS,
        ]);
    }

    public function store(Request $request, User $user)
    {
        if (! $request->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
        ]);

        UserAvailability::updateOrCreate(
            ['user_id' => $user->id, 'day_of_week' => $request->day_of_week],
            ['time_from' => $request->time_from, 'time_to' => $request->time_to]
        );

        return redirect()->back()->with('success', 'Disponibilità salvata.');
    }

    public function destroy(Request $request, UserAvailability $availability)
    {
        if (! $request->user()->hasPermissionTo('user-availabilities.manage')) {
            abort(403);
        }

        $availability->delete();

        return redirect()->back()->with('success', 'Disponibilità eliminata.');
    }
}
