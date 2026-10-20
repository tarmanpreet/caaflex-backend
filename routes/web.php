<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AutoConfirmSlotController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NexxworthSiteController;
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
use App\Http\Middleware\SetNexxworthEnglishLocale;
use App\Support\NexxworthSeo;
use Illuminate\Support\Facades\Route;

Route::get('/', [NexxworthSiteController::class, 'home'])->name('home');
Route::get('/preventivatore', [NexxworthSiteController::class, 'calculator'])->name('caaflex.calculator');
Route::get('/chi-siamo', [NexxworthSiteController::class, 'about'])->name('nexxworth.about');
Route::get('/servizi', [NexxworthSiteController::class, 'services'])->name('nexxworth.services');
Route::get('/collaborazioni', [NexxworthSiteController::class, 'partners'])->name('nexxworth.partners');
Route::get('/contatti', [NexxworthSiteController::class, 'contact'])->name('nexxworth.contact');
Route::get('/informativa-privacy', [NexxworthSiteController::class, 'privacy'])->name('nexxworth.privacy');
Route::get('/stato-pratica', [PublicPracticeStatusController::class, 'nexxworthIndex'])->name('nexxworth.status');
Route::post('/stato-pratica', [PublicPracticeStatusController::class, 'nexxworthLookup'])
    ->middleware('throttle:10,1')->name('nexxworth.status.lookup');
Route::post('/contatti', [NexxworthSiteController::class, 'submitContact'])
    ->middleware('throttle:5,1')
    ->name('nexxworth.contact.submit');

Route::middleware(SetNexxworthEnglishLocale::class)->prefix('en')->name('nexxworth.en.')->group(function (): void {
    Route::get('/', [NexxworthSiteController::class, 'englishHome'])->name('home');
    Route::get('/about', [NexxworthSiteController::class, 'englishAbout'])->name('about');
    Route::get('/services', [NexxworthSiteController::class, 'englishServices'])->name('services');
    Route::get('/partners', [NexxworthSiteController::class, 'englishPartners'])->name('partners');
    Route::get('/contact', [NexxworthSiteController::class, 'englishContact'])->name('contact');
    Route::get('/privacy', [NexxworthSiteController::class, 'englishPrivacy'])->name('privacy');
    Route::get('/check-status', [PublicPracticeStatusController::class, 'nexxworthIndex'])->name('status');
    Route::post('/check-status', [PublicPracticeStatusController::class, 'nexxworthLookup'])
        ->middleware('throttle:10,1')->name('status.lookup');
    Route::post('/contact', [NexxworthSiteController::class, 'submitContact'])
        ->middleware('throttle:5,1')
        ->name('contact.submit');
});

Route::get('/sitemap.xml', function () {
    abort_unless(config('branding.customer_code') === 'nexxworth', 404);

    return response()->view('nexxworth-sitemap', ['pages' => NexxworthSeo::sitemap()])
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('nexxworth.sitemap');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\nAllow: /\n";

    if (config('branding.customer_code') === 'nexxworth') {
        $content .= 'Sitemap: '.route('nexxworth.sitemap')."\n";
    }

    return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');

Route::get('/controlla-pratica', [PublicPracticeStatusController::class, 'index'])->name('practice-status.index');
Route::post('/controlla-pratica', [PublicPracticeStatusController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('practice-status.lookup');

Route::middleware([
    'auth:sanctum',
    'active-user',
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
    Route::get('/employees', [App\Http\Controllers\EmployeeAttendanceController::class, 'index'])->name('employees.index');
    Route::post('/employees/clock-in', [App\Http\Controllers\EmployeeAttendanceController::class, 'clockIn'])->name('employees.clockIn');
    Route::post('/employees/clock-out', [App\Http\Controllers\EmployeeAttendanceController::class, 'clockOut'])->name('employees.clockOut');
    Route::post('/employees/absences', [App\Http\Controllers\EmployeeAttendanceController::class, 'store'])->name('employees.store');
    Route::patch('/employees/absences/{absence}', [App\Http\Controllers\EmployeeAttendanceController::class, 'update'])->name('employees.update');
    Route::post('/employees/absences/{absence}/cancel', [App\Http\Controllers\EmployeeAttendanceController::class, 'cancel'])->name('employees.cancel');
    Route::post('/employees/absences/{absence}/review', [App\Http\Controllers\EmployeeAttendanceController::class, 'review'])->name('employees.review');
    Route::post('/employees/sessions', [App\Http\Controllers\EmployeeAttendanceController::class, 'manual'])->name('employees.manual');
    Route::patch('/employees/sessions/{session}', [App\Http\Controllers\EmployeeAttendanceController::class, 'correct'])->name('employees.correct');

    Route::get('/settings/notifications', [NotificationSettingsController::class, 'show'])->name('notification-settings.show');
    Route::put('/settings/notifications', [NotificationSettingsController::class, 'update'])->name('notification-settings.update');

    Route::resource('clients', ClientController::class);
    Route::get('/booking', App\Http\Controllers\BookingController::class)->name('booking.index');
    Route::get('/booking/options', [App\Http\Controllers\Api\V1\BookingController::class, 'options'])->name('booking.options');
    Route::get('/booking/slots', [App\Http\Controllers\Api\V1\BookingController::class, 'slots'])->name('booking.slots');
    Route::post('/booking', [App\Http\Controllers\Api\V1\BookingController::class, 'store'])->name('booking.store');
    Route::post('/booking/{appointment}/reschedule', [App\Http\Controllers\Api\V1\BookingController::class, 'reschedule'])->name('booking.reschedule');

    Route::get('/family', App\Http\Controllers\FamilyController::class)->name('family.index');
    Route::get('/family/data', [App\Http\Controllers\Api\V1\FamilyController::class, 'index'])->name('family.data');
    Route::get('/family/clients/{client}', App\Http\Controllers\FamilyController::class)->name('family.profile');
    Route::get('/family/clients/{client}/data', [App\Http\Controllers\Api\V1\FamilyController::class, 'profile'])->name('family.profile.data');
    Route::post('/family/clients/{client}/delegations', [App\Http\Controllers\Api\V1\FamilyController::class, 'grant'])->name('family.grant');
    Route::post('/family/delegations/{delegation}/accept', [App\Http\Controllers\Api\V1\FamilyController::class, 'accept'])->name('family.accept');
    Route::delete('/family/clients/{client}/delegations/{delegation}', [App\Http\Controllers\Api\V1\FamilyController::class, 'revoke'])->name('family.revoke');
    Route::get('/family/clients/{client}/documents/{document}/download', [App\Http\Controllers\Api\V1\FamilyController::class, 'download'])->name('family.download');

    Route::get('/clients-search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/{client}/invite-user', [ClientController::class, 'inviteUser'])->name('clients.invite-user');
    Route::resource('appointments', AppointmentController::class)->except(['create', 'edit']);
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::get('/appointments-practices', [AppointmentController::class, 'practicesForModal'])->name('appointments.practicesForModal');
    Route::get('/appointments-calendar', [AppointmentController::class, 'calendarEvents'])->name('appointments.calendarEvents');
    Route::resource('practices', PracticeController::class)->except(['edit']);
    Route::get('/search', [App\Http\Controllers\PracticeOperationsController::class, 'searchView'])->name('search.index');
    Route::get('/operations', App\Http\Controllers\PracticeOperationsController::class)->name('operations.index');
    Route::get('/operations/data', [App\Http\Controllers\Api\V1\PracticeOperationsController::class, 'index'])->name('operations.data');
    Route::get('/operations/search', [App\Http\Controllers\Api\V1\PracticeOperationsController::class, 'search'])->name('operations.search');
    Route::post('/operations/bulk', [App\Http\Controllers\Api\V1\PracticeOperationsController::class, 'bulk'])->name('operations.bulk');
    Route::post('/practices/{practice}/workspace/renew', [App\Http\Controllers\Api\V1\PracticeOperationsController::class, 'renew'])->name('practices.workspace.renew');

    Route::get('/practices/{practice}/workspace', App\Http\Controllers\PracticeWorkspaceController::class)->name('practices.workspace');
    Route::get('/practices/{practice}/workspace/data', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'index'])->name('practices.workspace.data');
    Route::post('/practices/{practice}/workspace/messages', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'message'])->name('practices.workspace.messages');
    Route::post('/practices/{practice}/workspace/requests', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'storeRequest'])->name('practices.workspace.requests');
    Route::post('/practices/{practice}/workspace/requests/{documentRequest}/documents', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'upload'])->name('practices.workspace.upload');
    Route::patch('/practices/{practice}/workspace/requests/{documentRequest}/documents/{document}/review', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'review'])->name('practices.workspace.review');
    Route::get('/practices/{practice}/workspace/documents/{document}/download', [App\Http\Controllers\Api\V1\PracticeWorkspaceController::class, 'download'])->name('practices.workspace.download');

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
    Route::patch('/practices/{practice}/deadlines/{deadline}/complete', [PracticeDeadlineController::class, 'complete'])->name('practices.deadlines.complete')->scopeBindings();
    Route::put('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'update'])->name('practices.deadlines.update')->scopeBindings();
    Route::delete('/practices/{practice}/deadlines/{deadline}', [PracticeDeadlineController::class, 'destroy'])->name('practices.deadlines.destroy')->scopeBindings();

    Route::post('/clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store')->scopeBindings();
    Route::patch('/clients/{client}/documents/{document}/expiration', [ClientDocumentController::class, 'updateExpiration'])->name('clients.documents.expiration.update')->scopeBindings();
    Route::get('/clients/{client}/documents/{document}/download', [ClientDocumentController::class, 'download'])->name('clients.documents.download')->scopeBindings();
    Route::delete('/clients/{client}/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('clients.documents.destroy')->scopeBindings();
});
