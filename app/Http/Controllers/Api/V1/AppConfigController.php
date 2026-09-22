<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $reverb = config('broadcasting.connections.reverb');

        return response()->json([
            'data' => [
                'branding' => [
                    'name' => config('branding.name'),
                    'logo_url' => config('branding.logo_url'),
                    'mark_url' => config('branding.mark_url'),
                    'favicon_url' => config('branding.favicon_url'),
                ],
                'realtime' => [
                    'enabled' => config('broadcasting.default') === 'reverb' && filled($reverb['key'] ?? null),
                    'broadcaster' => 'reverb',
                    'key' => $reverb['key'] ?? null,
                    'host' => data_get($reverb, 'options.host'),
                    'port' => (int) data_get($reverb, 'options.port', 443),
                    'scheme' => data_get($reverb, 'options.scheme', 'https'),
                ],
                'minimum_versions' => config('mobile.minimum_versions'),
                'features' => config('mobile.features'),
            ],
        ]);
    }
}
