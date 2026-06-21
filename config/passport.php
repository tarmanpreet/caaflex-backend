<?php

return [

    'guard' => 'web',

    'middleware' => [],

    'private_key' => trim(file_get_contents(storage_path('oauth-private.key'))),
    'public_key'  => trim(file_get_contents(storage_path('oauth-public.key'))),

    'connection' => env('PASSPORT_CONNECTION'),

];
