<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPracticeStatusRequest;
use App\Models\Practice;
use Inertia\Inertia;
use Inertia\Response;

class PublicPracticeStatusController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PracticeStatus');
    }

    public function lookup(LookupPracticeStatusRequest $request): Response
    {
        $trackingCode = $request->validated('code');
        $practice = Practice::query()
            ->select(['tracking_code', 'status'])
            ->where('tracking_code', $trackingCode)
            ->first();

        return Inertia::render('PracticeStatus', [
            'searchedCode' => $trackingCode,
            'result' => $practice ? [
                'code' => $practice->tracking_code,
                'status' => $practice->status,
            ] : null,
            'lookupError' => $practice ? null : 'Nessuna pratica trovata con questo codice. Controllalo e riprova.',
        ]);
    }
}
