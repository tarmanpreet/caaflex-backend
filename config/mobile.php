<?php

return [
    'minimum_versions' => [
        'ios' => env('MOBILE_MINIMUM_IOS_VERSION', '1.0.0'),
        'android' => env('MOBILE_MINIMUM_ANDROID_VERSION', '1.0.0'),
    ],

    'features' => [
        'dashboard' => true,
        'deadlines' => true,
        'branches' => true,
        'procedures' => true,
        'notifications' => true,
        'public_practice_status' => true,
        'account_security' => true,
    ],
];
