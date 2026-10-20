<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Http\Requests\EmployeeAbsenceRequest;
use App\Http\Requests\ReviewEmployeeAbsenceRequest;
use App\Models\AttendanceSession;
use App\Models\EmployeeAbsence;
use App\Services\EmployeeAttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeAttendanceController extends Controller
{
    public function __construct(private EmployeeAttendanceService $attendance) {}

    public function index(AttendanceRequest $request): Response
    {
        return Inertia::render('Employees/Index', ['attendance' => $this->attendance->overview($request->user(), $request->validated())]);
    }

    public function clockIn(AttendanceRequest $request): RedirectResponse
    {
        $this->attendance->clock($request->user(), true, $request->validated('note'));

        return back()->with('success', 'Ingresso registrato. Buon lavoro!');
    }

    public function clockOut(AttendanceRequest $request): RedirectResponse
    {
        $this->attendance->clock($request->user(), false, null);

        return back()->with('success', 'Uscita registrata.');
    }

    public function store(EmployeeAbsenceRequest $request): RedirectResponse
    {
        $this->attendance->requestAbsence($request->user(), $request->validated());

        return back()->with('success', 'Assenza inviata.');
    }

    public function update(EmployeeAbsenceRequest $request, EmployeeAbsence $absence): RedirectResponse
    {
        $this->attendance->updateAbsence($request->user(), $absence, $request->validated());

        return back()->with('success', 'Note e protocollo aggiornati.');
    }

    public function cancel(AttendanceRequest $request, EmployeeAbsence $absence): RedirectResponse
    {
        $this->attendance->cancel($request->user(), $absence);

        return back()->with('success', 'Richiesta annullata.');
    }

    public function review(ReviewEmployeeAbsenceRequest $request, EmployeeAbsence $absence): RedirectResponse
    {
        $this->attendance->review($request->user(), $absence, $request->validated());

        return back()->with('success', 'Richiesta aggiornata.');
    }

    public function manual(CorrectAttendanceRequest $request): RedirectResponse
    {
        $this->attendance->correct($request->user(), null, $request->validated());

        return back()->with('success', 'Timbratura manuale registrata nello storico.');
    }

    public function correct(CorrectAttendanceRequest $request, AttendanceSession $session): RedirectResponse
    {
        $this->attendance->correct($request->user(), $session, $request->validated());

        return back()->with('success', 'Rettifica registrata nello storico.');
    }
}
