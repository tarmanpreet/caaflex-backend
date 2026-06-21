<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildDashboardDataAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, BuildDashboardDataAction $action): Response
    {
        return Inertia::render('Dashboard', $action->execute($request->user()));
    }
}