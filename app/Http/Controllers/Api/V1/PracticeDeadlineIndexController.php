<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PracticeDeadline\IndexPracticeDeadlineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListPracticeDeadlinesRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PracticeDeadlineIndexController extends Controller
{
    public function __invoke(ListPracticeDeadlinesRequest $request, IndexPracticeDeadlineAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute($request, $request->user()),
            'summary' => $action->summary($request->user()),
            'filters' => $request->safe()->only(['search', 'status', 'priority', 'timing', 'sort', 'direction']),
            'users' => User::assignable()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }
}
