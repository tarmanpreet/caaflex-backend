<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AutoConfirmSlot;
use App\Models\Branch;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeDocument;
use App\Models\PracticeNote;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use App\Models\UserAvailability;
use App\Observers\MobileSyncObserver;
use App\Policies\ClientPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([ClientProfile::class, Practice::class, Appointment::class, Procedure::class,
            PracticeType::class, Branch::class, User::class, AutoConfirmSlot::class,
            UserAvailability::class, ClientDocument::class, PracticeDocument::class,
            PracticeDeadline::class, PracticeNote::class] as $model) {
            $model::observe(MobileSyncObserver::class);
        }
        // ClientProfile uses ClientPolicy (non-standard name: would be ClientProfilePolicy by convention)
        Gate::policy(ClientProfile::class, ClientPolicy::class);

        Gate::before(function ($user) {
            return $user->hasRole('superadmin') ? true : null;
        });
    }
}
