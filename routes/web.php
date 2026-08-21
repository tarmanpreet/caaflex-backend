<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AutoConfirmSlotController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\PracticeDeadlineController;
use App\Http\Controllers\PracticeDeadlineIndexController;
use App\Http\Controllers\PracticeDocumentController;
use App\Http\Controllers\PracticeNoteController;
use App\Http\Controllers\PracticeTypeController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\PublicPracticeStatusController;
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
})->name('home');

Route::get('/controlla-pratica', [PublicPracticeStatusController::class, 'index'])->name('practice-status.index');
Route::post('/controlla-pratica', [PublicPracticeStatusController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('practice-status.lookup');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/settings/notifications', [NotificationSettingsController::class, 'show'])->name('notification-settings.show');
    Route::put('/settings/notifications', [NotificationSettingsController::class, 'update'])->name('notification-settings.update');

    Route::resource('clients', ClientController::class);
    Route::get('/clients-search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/{client}/invite-user', [ClientController::class, 'inviteUser'])->name('clients.invite-user');
    Route::resource('appointments', AppointmentController::class)->except(['create', 'edit']);
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments-practices', [AppointmentController::class, 'practicesForModal'])->name('appointments.practicesForModal');
    Route::get('/appointments-calendar', [AppointmentController::class, 'calendarEvents'])->name('appointments.calendarEvents');
    Route::resource('practices', PracticeController::class)->except(['edit']);
    Route::get('/deadlines', PracticeDeadlineIndexController::class)->name('deadlines.index');
    Route::post('/practices/{practice}/assign', [PracticeController::class, 'assignUsers'])->name('practices.assign');

    Route::resource('practice-types', PracticeTypeController::class)->except(['show']);

    Route::resource('branches', BranchController::class)->except(['show']);

    Route::resource('procedures', ProcedureController::class)->except(['show']);

    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::get('/users/{user}/availabilities', [UserAvailabilityController::class, 'index'])->name('users.availabilities.index');
    Route::post('/users/{user}/availabilities', [UserAvailabilityController::class, 'store'])->name('users.availabilities.store');
    Route::delete('/availabilities/{availability}', [UserAvailabilityController::class, 'destroy'])->name('users.availabilities.destroy');

    Route::get('/auto-confirm-slots', [AutoConfirmSlotController::class, 'index'])->name('auto-confirm-slots.index');
    Route::post('/auto-confirm-slots', [AutoConfirmSlotController::class, 'store'])->name('auto-confirm-slots.store');
    Route::delete('/auto-confirm-slots/{slot}', [AutoConfirmSlotController::class, 'destroy'])->name('auto-confirm-slots.destroy');

    Route::post('/practices/{practice}/documents', [PracticeDocumentController::class, 'store'])->name('practices.documents.store')->scopeBindings();
    Route::patch('/practices/{practice}/documents/{document}/expiration', [PracticeDocumentController::class, 'updateExpiration'])->name('practices.documents.expiration.update')->scopeBindings();
    Route::get('/practices/{practice}/documents/{document}/download', [PracticeDocumentController::class, 'download'])->name('practices.documents.download')->scopeBindings();
    Route::delete('/practices/{practice}/documents/{document}', [PracticeDocumentController::class, 'destroy'])->name('practices.documents.destroy')->scopeBindings();

    Route::post('/practices/{practice}/notes', [PracticeNoteController::class, 'store'])->name('practices.notes.store');

    Route::post('/practices/{practice}/deadlines', [PracticeDeadlineController::class, 'store'])->name('practices.deadlines.store')->scopeBindings();
    Route::put('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'update'])->name('practices.deadlines.update')->scopeBindings();
    Route::delete('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'destroy'])->name('practices.deadlines.destroy')->scopeBindings();

    Route::post('/clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store')->scopeBindings();
    Route::patch('/clients/{client}/documents/{document}/expiration', [ClientDocumentController::class, 'updateExpiration'])->name('clients.documents.expiration.update')->scopeBindings();
    Route::get('/clients/{client}/documents/{document}/download', [ClientDocumentController::class, 'download'])->name('clients.documents.download')->scopeBindings();
    Route::delete('/clients/{client}/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('clients.documents.destroy')->scopeBindings();
});
