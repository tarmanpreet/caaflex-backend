<?php

namespace App\Http\Controllers;

use App\Models\Practice;
use App\Services\PracticeWorkspaceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeWorkspaceController extends Controller
{
    public function __invoke(Request $request, Practice $practice, PracticeWorkspaceService $workspace): Response
    {
        return Inertia::render('Practices/Workspace', ['workspace' => $workspace->data($practice, $request->user())]);
    }
}
