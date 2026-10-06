<?php

return [
    'name'     => env('APP_NAME', 'DeepCatch'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => env('APP_DEBUG', false),
    // Leave empty to auto-detect (works from a sub-folder or a document root pointing at /public).
    'url'      => env('APP_URL', ''),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'session' => [
        'name'          => 'deepcatch_session',
        'idle_timeout'  => 60 * 60 * 2,   // seconds of inactivity before logout
        'remember_days' => 30,
    ],

    'login_throttle' => [
        'max_attempts'    => 5,    // per identifier + IP
        'ip_max_attempts' => 25,   // per IP across all identifiers
        'decay_minutes'   => 15,
    ],

    'fish_api' => [
        'url'     => env('FISH_API_URL', 'https://marcconrad.com/uob/fish/api.php'),
        'timeout' => 8,
        'retries' => 1,
    ],

    'avatars' => [
        'max_kb' => 2048,
        'dir'    => 'uploads/avatars', // relative to /public
    ],
];
