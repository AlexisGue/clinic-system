<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS for the Vue SPA
    |--------------------------------------------------------------------------
    | Only the frontend origin is allowed (never "*" because we use
    | credentialed cookies). supports_credentials is required so the
    | browser sends/receives the session and XSRF cookies.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
