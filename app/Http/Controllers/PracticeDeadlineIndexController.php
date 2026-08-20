<?php

namespace App\Http\Controllers;

use App\Actions\PracticeDeadline\IndexPracticeDeadlineAction;
use App\Http\Requests\ListPracticeDeadlinesRequest;
use Inertia\Inertia;
use Inertia\Response;

class PracticeDeadlineIndexController extends Controller
{
    public function __invoke(ListPracticeDeadlinesRequest $request, IndexPracticeDeadlineAction $action): Response
    {
        return Inertia::render('Deadlines/Index', [
            'deadlines' => $action->execute($request, $request->user()),
            'summary' => $action->summary($request->user()),
            'filters' => $request->safe()->only(['search', 'status', 'priority', 'timing', 'sort', 'direction']),
        ]);
    }
}
