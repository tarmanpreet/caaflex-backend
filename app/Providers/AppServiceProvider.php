<?php

namespace App\Providers;

use App\Models\ClientProfile;
use App\Policies\ClientPolicy;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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

        Passport::authorizationView('passport.authorize');

        Passport::tokensExpireIn(CarbonInterval::hours(1));
        Passport::refreshTokensExpireIn(CarbonInterval::months(6));
        Passport::personalAccessTokensExpireIn(CarbonInterval::year());
    }
}
