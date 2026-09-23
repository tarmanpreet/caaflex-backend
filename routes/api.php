<?php

use Illuminate\Support\Facades\Route;

// API v1 routes
Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/app-config', App\Http\Controllers\Api\V1\AppConfigController::class)
        ->middleware('throttle:60,1')
        ->name('app-config');
    Route::post('/practice-status', App\Http\Controllers\Api\V1\PublicPracticeStatusController::class)
        ->middleware('throttle:10,1')
        ->name('practice-status');
    Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/tokens/refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh'])->middleware('throttle:30,1');
    Route::post('/email-password-reset', [App\Http\Controllers\Api\V1\AuthController::class, 'emailPasswordReset'])->middleware('throttle:5,1');
    Route::post('/password-reset', [App\Http\Controllers\Api\V1\AuthController::class, 'passwordReset'])->middleware('throttle:5,1');
    Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->middleware(['auth:api', 'active-user', 'access-token']);

    // Protected routes
    Route::middleware(['auth:api', 'active-user', 'access-token'])->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);

        // Account and security routes
        Route::put('/account/profile', [App\Http\Controllers\Api\V1\AccountController::class, 'updateProfile']);
        Route::put('/account/password', [App\Http\Controllers\Api\V1\AccountController::class, 'updatePassword']);
        Route::get('/account/two-factor', [App\Http\Controllers\Api\V1\AccountController::class, 'twoFactorStatus']);
        Route::post('/account/two-factor', [App\Http\Controllers\Api\V1\AccountController::class, 'enableTwoFactor']);
        Route::post('/account/two-factor/confirm', [App\Http\Controllers\Api\V1\AccountController::class, 'confirmTwoFactor']);
        Route::delete('/account/two-factor', [App\Http\Controllers\Api\V1\AccountController::class, 'disableTwoFactor']);
        Route::get('/account/two-factor/qr-code', [App\Http\Controllers\Api\V1\AccountController::class, 'twoFactorQrCode']);
        Route::get('/account/two-factor/recovery-codes', [App\Http\Controllers\Api\V1\AccountController::class, 'recoveryCodes']);
        Route::post('/account/two-factor/recovery-codes', [App\Http\Controllers\Api\V1\AccountController::class, 'regenerateRecoveryCodes']);
        Route::delete('/account/other-sessions', [App\Http\Controllers\Api\V1\AccountController::class, 'logoutOtherSessions']);
        Route::delete('/account', [App\Http\Controllers\Api\V1\AccountController::class, 'destroy']);

        Route::match(['get', 'post'], '/broadcasting/auth', [Illuminate\Broadcasting\BroadcastController::class, 'authenticate']);

        // Client routes
        Route::get('/clients/search', [App\Http\Controllers\Api\V1\ClientController::class, 'search'])->name('clients.search');
        Route::apiResource('/clients', App\Http\Controllers\Api\V1\ClientController::class);

        // Client account/invite routes
        Route::post('/clients/{client}/invite-user', [App\Http\Controllers\Api\V1\ClientController::class, 'inviteUser']);

        // Client document routes
        Route::post('/clients/{client}/documents', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'store'])->scopeBindings();
        Route::patch('/clients/{client}/documents/{document}/expiration', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'updateExpiration'])->scopeBindings();
        Route::get('/clients/{client}/documents/{document}/download', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'download'])->scopeBindings();
        Route::delete('/clients/{client}/documents/{document}', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'destroy'])->scopeBindings();

        // Practice routes
        Route::apiResource('/practices', App\Http\Controllers\Api\V1\PracticeController::class);
        Route::post('/practices/{practice}/assign', [App\Http\Controllers\Api\V1\PracticeController::class, 'assignUsers']);

        // Practice note routes
        Route::get('/practices/{practice}/notes', [App\Http\Controllers\Api\V1\PracticeNoteController::class, 'index']);
        Route::post('/practices/{practice}/notes', [App\Http\Controllers\Api\V1\PracticeNoteController::class, 'store']);

        // Practice deadline routes
        Route::get('/practices/{practice}/deadlines', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'index'])->name('practices.deadlines.index');
        Route::post('/practices/{practice}/deadlines', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'store'])->name('practices.deadlines.store')->scopeBindings();
        Route::get('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'show'])->name('practices.deadlines.show')->scopeBindings();
        Route::patch('/practices/{practice}/deadlines/{deadline}/complete', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'complete'])->name('practices.deadlines.complete')->scopeBindings();
        Route::put('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'update'])->name('practices.deadlines.update')->scopeBindings();
        Route::delete('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'destroy'])->name('practices.deadlines.destroy')->scopeBindings();

        // Practice document routes
        Route::post('/practices/{practice}/documents', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'store'])->scopeBindings();
        Route::patch('/practices/{practice}/documents/{document}/expiration', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'updateExpiration'])->scopeBindings();
        Route::get('/practices/{practice}/documents/{document}/download', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'download'])->scopeBindings();
        Route::delete('/practices/{practice}/documents/{document}', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'destroy'])->scopeBindings();

        // Procedure routes
        Route::apiResource('/procedures', App\Http\Controllers\Api\V1\ProcedureController::class);

        // Appointment routes
        Route::patch('/appointments/{appointment}/cancel', [App\Http\Controllers\Api\V1\AppointmentController::class, 'cancel']);
        Route::apiResource('/appointments', App\Http\Controllers\Api\V1\AppointmentController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('/appointments-manage', [App\Http\Controllers\Api\V1\AppointmentController::class, 'manageIndex']);
        Route::patch('/appointments/{appointment}/reschedule', [App\Http\Controllers\Api\V1\AppointmentController::class, 'reschedule']);
        Route::get('/appointments-calendar', [App\Http\Controllers\Api\V1\AppointmentController::class, 'calendarEvents']);
        Route::get('/appointments-practices', [App\Http\Controllers\Api\V1\AppointmentController::class, 'practicesForModal']);
        Route::get('/practice-types', [App\Http\Controllers\Api\V1\AppointmentController::class, 'practiceTypes']);

        // Notification routes
        Route::get('/notifications', [App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/read', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/{notification}/open', [App\Http\Controllers\Api\V1\NotificationController::class, 'open']);
        Route::post('/notifications/read-all', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);
        Route::get('/notification-settings', [App\Http\Controllers\Api\V1\NotificationSettingsController::class, 'show']);
        Route::put('/notification-settings', [App\Http\Controllers\Api\V1\NotificationSettingsController::class, 'update']);
        Route::post('/push-tokens', [App\Http\Controllers\Api\V1\ExpoPushTokenController::class, 'store']);
        Route::delete('/push-tokens/{expoPushToken}', [App\Http\Controllers\Api\V1\ExpoPushTokenController::class, 'destroy']);

        // Dashboard routes
        Route::get('/dashboard', App\Http\Controllers\Api\V1\DashboardController::class)->name('dashboard');
        Route::get('/dashboard/notices', [App\Http\Controllers\Api\V1\DashboardNoticeController::class, 'index']);

        Route::get('/deadlines', App\Http\Controllers\Api\V1\PracticeDeadlineIndexController::class)->name('deadlines.index');

        // Practice type routes
        Route::apiResource('/practice-types-manage', App\Http\Controllers\Api\V1\PracticeTypeController::class)
            ->except(['show'])->parameters(['practice-types-manage' => 'practice_type']);

        // User routes (explicit routes before apiResource to avoid parameter conflicts)
        Route::get('/users/available', [App\Http\Controllers\Api\V1\AppointmentController::class, 'availableUsers']);
        Route::apiResource('/users', App\Http\Controllers\Api\V1\UserController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('/users/{user}/toggle-active', [App\Http\Controllers\Api\V1\UserController::class, 'toggleActive']);

        // User availability routes
        Route::get('/users/{user}/availabilities', [App\Http\Controllers\Api\V1\UserAvailabilityController::class, 'index']);
        Route::post('/users/{user}/availabilities', [App\Http\Controllers\Api\V1\UserAvailabilityController::class, 'store']);
        Route::delete('/availabilities/{availability}', [App\Http\Controllers\Api\V1\UserAvailabilityController::class, 'destroy']);

        // Auto confirm slot routes
        Route::get('/auto-confirm-slots', [App\Http\Controllers\Api\V1\AutoConfirmSlotController::class, 'index']);
        Route::post('/auto-confirm-slots', [App\Http\Controllers\Api\V1\AutoConfirmSlotController::class, 'store']);
        Route::delete('/auto-confirm-slots/{slot}', [App\Http\Controllers\Api\V1\AutoConfirmSlotController::class, 'destroy']);

        // Branch routes
        Route::get('/branches/active', [App\Http\Controllers\Api\V1\BranchController::class, 'active']);
        Route::apiResource('/branches', App\Http\Controllers\Api\V1\BranchController::class);
        Route::post('/branches/{branch}/sync-employees', [App\Http\Controllers\Api\V1\BranchController::class, 'syncEmployees']);
    });
});
