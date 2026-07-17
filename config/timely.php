<?php

return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'timeout' => env('TIMELY_API_TIMEOUT', 15),

    'oauth' => [
        'authorize_url' => env('TIMELY_OAUTH_AUTHORIZE_URL', 'https://api.timelyapp.com/1.1/oauth/authorize'),
        'token_url' => env('TIMELY_OAUTH_TOKEN_URL', 'https://api.timelyapp.com/1.1/oauth/token'),
        'client_id' => env('TIMELY_OAUTH_CLIENT_ID'),
        'client_secret' => env('TIMELY_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('TIMELY_OAUTH_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob'),
        'scope' => 'manage',
    ],

    'access_token' => null,
    'refresh_token' => null,
    'token_expires_at' => null,

    'account_id' => env('TIMELY_ACCOUNT_ID'),
    'user_id' => env('TIMELY_USER_ID'),
    'created_at' => env('TIMELY_CREATED_AT'),

    'since' => env('TIMELY_SINCE'),
];
