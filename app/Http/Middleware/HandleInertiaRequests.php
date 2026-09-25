<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'branding' => [
                'customer_code' => config('branding.customer_code'),
                'name' => config('branding.name'),
                'logo_url' => config('branding.logo_url'),
                'logo_light_url' => config('branding.logo_light_url'),
                'mark_url' => config('branding.mark_url'),
                'favicon_url' => config('branding.favicon_url'),
            ],
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ]) : null,
            ],
            'realtime' => [
                'enabled' => config('broadcasting.default') === 'reverb'
                    && filled(config('broadcasting.connections.reverb.key')),
                'key' => config('broadcasting.connections.reverb.key'),
            ],
            'flash' => [
                'success' => fn (): mixed => $request->session()->get('success'),
                'error' => fn (): mixed => $request->session()->get('error'),
            ],
        ];
    }
}
