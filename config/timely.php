<?php

use App\Enums\ConfigKey;

return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'timeout' => env('TIMELY_API_TIMEOUT', 15),

    'oauth' => [
        'authorize_url' => env('TIMELY_OAUTH_AUTHORIZE_URL', 'https://api.timelyapp.com/1.1/oauth/authorize'),
        'token_url' => env('TIMELY_OAUTH_TOKEN_URL', 'https://api.timelyapp.com/1.1/oauth/token'),
        'client_id' => ConfigKey::OAuthClientId->envValue(),
        'client_secret' => ConfigKey::OAuthClientSecret->envValue(),
        'redirect_uri' => ConfigKey::OAuthRedirectUri->envValue('urn:ietf:wg:oauth:2.0:oob'),
        'scope' => 'manage',
    ],

    'tokens' => [
        'access' => ConfigKey::AccessToken->envValue(),
        'refresh' => ConfigKey::RefreshToken->envValue(),
        'expires_at' => ConfigKey::TokenExpiresAt->envValue(),
    ],

    'account' => [
        'id' => ConfigKey::AccountId->envValue(),
    ],

    'user' => [
        'id' => ConfigKey::UserId->envValue(),
        'created_at' => ConfigKey::CreatedAt->envValue(),
    ],

    'report' => [
        'since' => ConfigKey::Since->envValue(),
    ],
];
