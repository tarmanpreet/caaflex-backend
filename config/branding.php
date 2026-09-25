<?php

$customerCode = env('CUSTOMER_CODE', 'caaflex');

if ($customerCode === 'nexxworth') {
    return [
        'customer_code' => 'nexxworth',
        'name' => 'Nexxworth Consulting',
        'logo_url' => '/brand/nexxworth/logo.png',
        'logo_light_url' => '/brand/nexxworth/logo.png',
        'mark_url' => '/brand/nexxworth/mark.png',
        'favicon_url' => '/brand/nexxworth/mark.png',
        'contact_email' => 'ksd.servizi@gmail.com',
    ];
}

return [
    'customer_code' => 'caaflex',
    'name' => env('BRAND_NAME') ?: config('app.name'),
    'logo_url' => env('BRAND_LOGO_URL', '/brand/caaflex-logo.png'),
    'logo_light_url' => env('BRAND_LOGO_LIGHT_URL', '/brand/caaflex-logo-light.png'),
    'mark_url' => env('BRAND_MARK_URL', '/brand/caaflex-mark.png'),
    'favicon_url' => env('BRAND_FAVICON_URL', '/brand/caaflex-mark.png'),
];
