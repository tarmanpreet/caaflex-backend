<?php

return [
    'name' => env('BRAND_NAME') ?: config('app.name'),
    'logo_url' => env('BRAND_LOGO_URL', '/brand/caf-gestionale-logo.svg'),
    'mark_url' => env('BRAND_MARK_URL', '/brand/caf-gestionale-mark.svg'),
    'favicon_url' => env('BRAND_FAVICON_URL', '/favicon.ico'),
];
