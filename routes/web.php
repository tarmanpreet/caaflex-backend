<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AutoConfirmSlotController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\PracticeDeadlineController;
use App\Http\Controllers\PracticeDocumentController;
use App\Http\Controllers\PracticeNoteController;
use App\Http\Controllers\PracticeTypeController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserAvailabilityController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::get('/clients-search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/{client}/invite-user', [ClientController::class, 'inviteUser'])->name('clients.invite-user');
    Route::resource('appointments', AppointmentController::class)->except(['create', 'edit']);
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments-practices', [AppointmentController::class, 'practicesForModal'])->name('appointments.practicesForModal');
    Route::get('/appointments-calendar', [AppointmentController::class, 'calendarEvents'])->name('appointments.calendarEvents');
    Route::resource('practices', PracticeController::class)->except(['edit']);
    Route::post('/practices/{practice}/assign', [PracticeController::class, 'assignUsers'])->name('practices.assign');

    Route::resource('practice-types', PracticeTypeController::class)->except(['show']);

    Route::resource('branches', BranchController::class)->except(['show']);

    Route::resource('procedures', ProcedureController::class)->except(['show']);

    Route::resource('users', UserController::class)->only(['index', 'show', 'update']);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::get('/users/{user}/availabilities', [UserAvailabilityController::class, 'index'])->name('users.availabilities.index');
    Route::post('/users/{user}/availabilities', [UserAvailabilityController::class, 'store'])->name('users.availabilities.store');
    Route::delete('/availabilities/{availability}', [UserAvailabilityController::class, 'destroy'])->name('users.availabilities.destroy');

    Route::get('/auto-confirm-slots', [AutoConfirmSlotController::class, 'index'])->name('auto-confirm-slots.index');
    Route::post('/auto-confirm-slots', [AutoConfirmSlotController::class, 'store'])->name('auto-confirm-slots.store');
    Route::delete('/auto-confirm-slots/{slot}', [AutoConfirmSlotController::class, 'destroy'])->name('auto-confirm-slots.destroy');

    Route::post('/practices/{practice}/documents', [PracticeDocumentController::class, 'store'])->name('practices.documents.store');
    Route::get('/practices/{practice}/documents/{document}/download', [PracticeDocumentController::class, 'download'])->name('practices.documents.download');
    Route::delete('/practices/{practice}/documents/{document}', [PracticeDocumentController::class, 'destroy'])->name('practices.documents.destroy');

    Route::post('/practices/{practice}/notes', [PracticeNoteController::class, 'store'])->name('practices.notes.store');

    Route::post('/practices/{practice}/deadlines', [PracticeDeadlineController::class, 'store'])->name('practices.deadlines.store');
    Route::put('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'update'])->name('practices.deadlines.update');
    Route::delete('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'destroy'])->name('practices.deadlines.destroy');

    Route::post('/clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store');
    Route::get('/clients/{client}/documents/{document}/download', [ClientDocumentController::class, 'download'])->name('clients.documents.download');
    Route::delete('/clients/{client}/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('clients.documents.destroy');
});
