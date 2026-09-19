<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | greyon-engine routes live on the web stack (/login, /admin/*, /catalog…).
    | Quasar may run on any localhost port (9000 default, 9400 in local tests).
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_unique([
        env('FRONTEND_URL'),
        'http://localhost:9000',
        'http://127.0.0.1:9000',
        'http://localhost:9400',
        'http://127.0.0.1:9400',
    ]))),

    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
