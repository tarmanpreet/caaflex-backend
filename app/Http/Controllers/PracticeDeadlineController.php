<?php

namespace App\Http\Controllers;

use App\Actions\PracticeDeadline\StorePracticeDeadlineAction;
use App\Actions\PracticeDeadline\UpdatePracticeDeadlineAction;
use App\Http\Requests\StorePracticeDeadlineRequest;
use App\Http\Requests\UpdatePracticeDeadlineRequest;
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

    public function store(StorePracticeDeadlineRequest $request, Practice $practice, StorePracticeDeadlineAction $action): RedirectResponse
    {
        $this->authorize('createDeadline', $practice);

        $action->execute($request->validated(), $practice, $request->user()->id);

        return redirect()->back()->with('success', 'Scadenza creata.');
    }

    public function update(UpdatePracticeDeadlineRequest $request, Practice $practice, PracticeDeadline $deadline, UpdatePracticeDeadlineAction $action): RedirectResponse
    {
        $this->authorize('updateDeadline', $practice);

        $action->execute($request->validated(), $practice, $deadline, $request->user()->id);

        return redirect()->back()->with('success', 'Scadenza aggiornata.');
    }

    public function destroy(Practice $practice, PracticeDeadline $deadline): RedirectResponse
    {
        $this->authorize('deleteDeadline', $practice);

        $deadline->delete();

        return redirect()->back()->with('success', 'Scadenza eliminata.');
    }
}
