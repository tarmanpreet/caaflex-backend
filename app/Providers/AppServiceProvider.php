<?php

namespace App\Providers;

use App\Models\ClientProfile;
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
        // ClientProfile uses ClientPolicy (non-standard name: would be ClientProfilePolicy by convention)
        Gate::policy(ClientProfile::class, ClientPolicy::class);

        Gate::before(function ($user) {
            return $user->hasRole('superadmin') ? true : null;
        });
    }
}
