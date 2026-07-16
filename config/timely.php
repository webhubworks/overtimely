<?php

return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'timeout' => env('TIMELY_API_TIMEOUT', 15),

    'oauth' => [
        'authorize_url' => env('TIMELY_OAUTH_AUTHORIZE_URL', 'https://api.timelyapp.com/1.1/oauth/authorize'),
        'token_url' => env('TIMELY_OAUTH_TOKEN_URL', 'https://api.timelyapp.com/1.1/oauth/token'),
        'client_id' => env('TIMELY_OAUTH_CLIENT_ID'),
        'client_secret' => env('TIMELY_OAUTH_CLIENT_SECRET'),
        'scope' => 'manage',
        'redirect_uri' => env('TIMELY_OAUTH_REDIRECT_URI'),
    ],

    'access_token' => null,
    'refresh_token' => null,
    'token_expires_at' => null,

    'account_id' => env('TIMELY_ACCOUNT_ID'),
    'user_id' => env('TIMELY_USER_ID'),
    'created_at' => null,
    'since' => env('TIMELY_SINCE'),
];
