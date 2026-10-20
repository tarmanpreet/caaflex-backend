<?php

namespace App\Http\Controllers;

use App\Services\PracticeOperationsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeOperationsController extends Controller
{
    public function searchView(): Response
    {
        return Inertia::render('Practices/Operations', ['operations' => null]);
    }

    public function __invoke(Request $request, PracticeOperationsService $operations): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('practices.index');
    }
}
