<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNexxworthContactRequest;
use App\Mail\NexxworthContactMail;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class NexxworthSiteController extends Controller
{
    public function home(): Response
    {
        if (config('branding.customer_code') === 'nexxworth') {
            return Inertia::render('Public/Nexxworth/Home');
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }

    public function about(): Response
    {
        return $this->renderPage('About');
    }

    public function services(): Response
    {
        return $this->renderPage('Services');
    }

    public function partners(): Response
    {
        return $this->renderPage('Partners');
    }

    public function contact(): Response
    {
        return $this->renderPage('Contact');
    }

    public function privacy(): Response
    {
        return $this->renderPage('Privacy');
    }

    public function submitContact(StoreNexxworthContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Mail::to(config('branding.contact_email'))->send(new NexxworthContactMail($data));

        return redirect()->route('nexxworth.contact')->with('success', 'Messaggio inviato. Ti risponderemo al più presto.');
    }

    private function renderPage(string $page): Response
    {
        abort_unless(config('branding.customer_code') === 'nexxworth', 404);

        return Inertia::render("Public/Nexxworth/{$page}");
    }
}
