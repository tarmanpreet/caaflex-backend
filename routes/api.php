<?php

use Illuminate\Support\Facades\Route;

// API v1 routes
Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->middleware('auth:api');

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);

        // Client routes
        Route::get('/clients/search', [App\Http\Controllers\Api\V1\ClientController::class, 'search'])->name('clients.search');
        Route::apiResource('/clients', App\Http\Controllers\Api\V1\ClientController::class);

        // Client account/invite routes
        Route::post('/clients/{client}/invite-user', [App\Http\Controllers\Api\V1\ClientController::class, 'inviteUser']);

        // Client document routes
        Route::post('/clients/{client}/documents', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'store']);
        Route::get('/clients/{client}/documents/{document}/download', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'download']);
        Route::delete('/clients/{client}/documents/{document}', [App\Http\Controllers\Api\V1\ClientDocumentController::class, 'destroy']);

        // Practice routes
        Route::apiResource('/practices', App\Http\Controllers\Api\V1\PracticeController::class);
        Route::post('/practices/{practice}/assign', [App\Http\Controllers\Api\V1\PracticeController::class, 'assignUsers']);

        // Practice note routes
        Route::get('/practices/{practice}/notes', [App\Http\Controllers\Api\V1\PracticeNoteController::class, 'index']);
        Route::post('/practices/{practice}/notes', [App\Http\Controllers\Api\V1\PracticeNoteController::class, 'store']);

        // Practice deadline routes
        Route::get('/practices/{practice}/deadlines', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'index'])->name('practices.deadlines.index');
        Route::post('/practices/{practice}/deadlines', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'store'])->name('practices.deadlines.store');
        Route::get('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'show'])->name('practices.deadlines.show');
        Route::put('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'update'])->name('practices.deadlines.update');
        Route::delete('/practices/{practice}/deadlines/{deadline}', [App\Http\Controllers\Api\V1\PracticeDeadlineController::class, 'destroy'])->name('practices.deadlines.destroy');

        // Practice document routes
        Route::post('/practices/{practice}/documents', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'store']);
        Route::get('/practices/{practice}/documents/{document}/download', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'download']);
        Route::delete('/practices/{practice}/documents/{document}', [App\Http\Controllers\Api\V1\PracticeDocumentController::class, 'destroy']);

        // Procedure routes
        Route::apiResource('/procedures', App\Http\Controllers\Api\V1\ProcedureController::class);

        // Appointment routes
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
        Route::post('/notifications/read-all', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);

        // Dashboard routes
        Route::get('/dashboard/notices', [App\Http\Controllers\Api\V1\DashboardNoticeController::class, 'index']);

        // Practice type routes
        Route::apiResource('/practice-types-manage', App\Http\Controllers\Api\V1\PracticeTypeController::class)
            ->except(['show'])->parameters(['practice-types-manage' => 'practice_type']);

        // User routes (explicit routes before apiResource to avoid parameter conflicts)
        Route::get('/users/available', [App\Http\Controllers\Api\V1\AppointmentController::class, 'availableUsers']);
        Route::apiResource('/users', App\Http\Controllers\Api\V1\UserController::class)
            ->only(['index', 'show', 'store', 'update']);
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
