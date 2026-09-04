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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeDeadlineController extends Controller
{
    use AuthorizesRequests;

    public function index(Practice $practice)
    {
        $this->authorize('viewDeadline', $practice);

        return redirect()->route('practices.show', $practice->id)->withFragment('steps');
    }

    public function store(StorePracticeDeadlineRequest $request, Practice $practice, StorePracticeDeadlineAction $action): RedirectResponse
    {
        $this->authorize('createDeadline', $practice);

        $action->execute($request->validated(), $practice, $request->user()->id);

        return redirect()->back()->with('success', 'Step creato.');
    }

    public function update(UpdatePracticeDeadlineRequest $request, Practice $practice, PracticeDeadline $deadline, UpdatePracticeDeadlineAction $action): RedirectResponse
    {
        $this->authorize('updateDeadline', $practice);

        $action->execute($request->validated(), $practice, $deadline, $request->user()->id);

        return redirect()->back()->with('success', 'Step aggiornato.');
    }

    public function complete(Request $request, Practice $practice, PracticeDeadline $deadline, UpdatePracticeDeadlineAction $action): RedirectResponse
    {
        $this->authorize('updateDeadline', $practice);

        $action->execute(['status' => PracticeDeadline::STATUS_COMPLETED], $practice, $deadline, $request->user()->id);

        return redirect()->back()->with('success', 'Step completato.');
    }

    public function destroy(Practice $practice, PracticeDeadline $deadline): RedirectResponse
    {
        $this->authorize('deleteDeadline', $practice);

        DB::transaction(function () use ($deadline): void {
            $deadline->steps()->delete();
            $deadline->delete();
        });

        return redirect()->back()->with('success', 'Step eliminato.');
    }
}
