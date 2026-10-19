<?php

namespace App\Http\Controllers;

use App\Models\ClientProfile;
use App\Services\FamilyAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function __invoke(Request $request, FamilyAccessService $family, ?ClientProfile $client = null): Response
    {
        return Inertia::render('Clients/Family', ['family' => $family->index($request->user()), 'profile' => $client ? $family->profile($request->user(), $client) : null]);
    }
}
