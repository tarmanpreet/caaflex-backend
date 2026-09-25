<?php

return [
    'name' => env('BRAND_NAME') ?: config('app.name'),
    'logo_url' => env('BRAND_LOGO_URL', '/brand/caaflex-logo.png'),
    'logo_light_url' => env('BRAND_LOGO_LIGHT_URL', '/brand/caaflex-logo-light.png'),
    'mark_url' => env('BRAND_MARK_URL', '/brand/caaflex-mark.png'),
    'favicon_url' => env('BRAND_FAVICON_URL', '/brand/caaflex-mark.png'),
];
