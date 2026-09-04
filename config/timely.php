<?php

use App\Enums\Setting;

return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'timeout' => env('TIMELY_API_TIMEOUT', 15),

    'oauth' => [
        'authorize_url' => env('TIMELY_OAUTH_AUTHORIZE_URL', 'https://api.timelyapp.com/1.1/oauth/authorize'),
        'token_url' => env('TIMELY_OAUTH_TOKEN_URL', 'https://api.timelyapp.com/1.1/oauth/token'),
        'client_id' => Setting::ClientId->envValue(),
        'client_secret' => Setting::ClientSecret->envValue(),
        'redirect_uri' => Setting::RedirectUri->envValue('urn:ietf:wg:oauth:2.0:oob'),
        'scope' => 'manage',
    ],

    'tokens' => [
        'access' => Setting::AccessToken->envValue(),
        'refresh' => Setting::RefreshToken->envValue(),
        'expires_at' => Setting::TokenExpiresAt->envValue(),
    ],

    'account' => [
        'id' => Setting::AccountId->envValue(),
    ],

    'user' => [
        'id' => Setting::UserId->envValue(),
        'created_at' => Setting::UserCreatedAt->envValue(),
    ],

    'report' => [
        'fetch_mode' => Setting::ReportFetchMode->envValue(),
        'since' => Setting::ReportSince->envValue(),
    ],
];
