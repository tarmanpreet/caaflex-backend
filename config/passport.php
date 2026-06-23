<?php

return [

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption keys
    |--------------------------------------------------------------------------
    | When left empty, Passport falls back to loading the RSA key files from
    | storage/oauth-private.key and storage/oauth-public.key (see
    | PassportServiceProvider::makeCryptKey()). The entrypoint generates these
    | automatically on first boot if they are missing.
    |
    | Do NOT call file_get_contents() here: it raises a warning (converted to
    | an ErrorException by Laravel) when the files are absent, which crashes
    | the app — and even `passport:keys` — before the keys can be created.
    |
    | For multi-replica deployments, set PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY
    | (full PEM contents) so all replicas share the same keys.
    */
    'private_key' => env('PASSPORT_PRIVATE_KEY'),
    'public_key'  => env('PASSPORT_PUBLIC_KEY'),

    'connection' => env('PASSPORT_CONNECTION'),

];
