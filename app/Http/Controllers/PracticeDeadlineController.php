<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeDeadlineRequest;
use App\Http\Requests\UpdatePracticeDeadlineRequest;
use App\Models\DeadlineReminder;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class PracticeDeadlineController extends Controller
{
    use AuthorizesRequests;

    public function index(Practice $practice)
    {
        $this->authorize('viewDeadline', $practice);

        return redirect()->route('practices.show', $practice->id);
    }

    public function store(StorePracticeDeadlineRequest $request, Practice $practice): RedirectResponse
    {
        $this->authorize('createDeadline', $practice);

        $data = $request->validated();
        $data['practice_id'] = $practice->id;
        $data['created_by'] = auth()->user()->id;

        if (!isset($data['status'])) {
            $data['status'] = PracticeDeadline::STATUS_PENDING;
        }

        $deadline = PracticeDeadline::create($data);

        DeadlineReminder::create([
            'deadline_id' => $deadline->id,
            'type' => DeadlineReminder::TYPE_EMAIL,
            'minutes_before' => DeadlineReminder::MINUTES_DAY,
            'sent' => false,
        ]);

        DeadlineReminder::create([
            'deadline_id' => $deadline->id,
            'type' => DeadlineReminder::TYPE_IN_APP,
            'minutes_before' => DeadlineReminder::MINUTES_HOUR,
            'sent' => false,
        ]);

        return redirect()->back()->with('success', 'Scadenza creata.');
    }

    public function update(UpdatePracticeDeadlineRequest $request, Practice $practice, PracticeDeadline $deadline): RedirectResponse
    {
        $this->authorize('updateDeadline', $practice);

        $deadline->update($request->validated());

        return redirect()->back()->with('success', 'Scadenza aggiornata.');
    }

    public function destroy(Practice $practice, PracticeDeadline $deadline): RedirectResponse
    {
        $this->authorize('deleteDeadline', $practice);

        $deadline->delete();

        return redirect()->back()->with('success', 'Scadenza eliminata.');
    }
}
