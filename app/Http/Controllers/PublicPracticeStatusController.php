<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPracticeStatusRequest;
use App\Models\Practice;
use Inertia\Inertia;
use Inertia\Response;

class PublicPracticeStatusController extends Controller
{
    public function nexxworthIndex(): Response
    {
        abort_unless(config('branding.customer_code') === 'nexxworth', 404);

        return Inertia::render('Public/Nexxworth/Status', [
            'locale' => app()->getLocale() === 'en' ? 'en' : 'it',
        ]);
    }

    public function nexxworthLookup(LookupPracticeStatusRequest $request): Response
    {
        abort_unless(config('branding.customer_code') === 'nexxworth', 404);

        $trackingCode = $request->validated('code');
        $practice = Practice::query()
            ->select(['tracking_code', 'status'])
            ->where('tracking_code', $trackingCode)
            ->first();

        return Inertia::render('Public/Nexxworth/Status', [
            'locale' => app()->getLocale() === 'en' ? 'en' : 'it',
            'searchedCode' => $trackingCode,
            'result' => $practice ? [
                'code' => $practice->tracking_code,
                'status' => $practice->status,
            ] : null,
            'lookupError' => $practice ? null : (app()->getLocale() === 'en'
                ? 'No case was found with this code. Please check it and try again.'
                : 'Nessuna pratica trovata con questo codice. Controllalo e riprova.'),
        ]);
    }

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
