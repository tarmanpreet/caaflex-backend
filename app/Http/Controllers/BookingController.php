<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function __invoke(Request $request): \Inertia\Response
    {
        return Inertia::render('Appointments/Booking', ['clientId' => $request->integer('client_profile_id') ?: null]);
    }
}
