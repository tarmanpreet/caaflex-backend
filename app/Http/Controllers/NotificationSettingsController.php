<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function show(NotificationPreferenceService $preferences): Response
    {
        return Inertia::render('NotificationSettings/Index', [
            'sections' => $preferences->settings(request()->user()),
            'reminderOptions' => collect(config('notifications.reminder_options'))
                ->map(fn (string $label, int|string $minutes): array => [
                    'value' => (int) $minutes,
                    'label' => $label,
                ])->values(),
        ]);
    }

    public function update(UpdateNotificationSettingsRequest $request, NotificationPreferenceService $preferences): RedirectResponse
    {
        $preferences->update($request->user(), $request->validated('sections'));

        return back()->with('success', 'Impostazioni notifiche aggiornate.');
    }
}
