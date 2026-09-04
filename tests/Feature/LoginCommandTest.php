<?php

use App\Enums\Setting;
use App\Support\UserConfig;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('timely.oauth', [
        'authorize_url' => 'https://timely.test/oauth/authorize',
        'token_url' => 'https://timely.test/oauth/token',
        'client_id' => 'cid',
        'client_secret' => 'secret',
        'scope' => 'manage',
        'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
    ]);
});

it('exchanges a pasted code and stores the tokens', function () {
    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'at',
            'refresh_token' => 'rt',
            'expires_in' => 3600,
            'created_at' => 1000,
            'scope' => 'manage',
            'token_type' => 'Bearer',
        ]),
    ]);

    $this->artisan('auth:login', ['code' => 'code123'])->assertSuccessful();

    expect(UserConfig::get(Setting::AccessToken))->toBe('at')
        ->and(UserConfig::get(Setting::RefreshToken))->toBe('rt')
        ->and((int) UserConfig::get(Setting::TokenExpiresAt))->toBe(4600);
});

it('fails non-interactively when the oauth app is not configured', function () {
    Setting::ClientId->setConfigValue(null);
    Setting::ClientSecret->setConfigValue(null);
    Setting::RedirectUri->setConfigValue(null);

    $this->artisan('auth:login', ['code' => 'code123', '--no-interaction' => true])->assertFailed();
});

it('persists the oauth application even when it came from the environment', function () {
    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'at',
            'refresh_token' => 'rt',
            'expires_in' => 3600,
            'created_at' => 1000,
            'scope' => 'manage',
            'token_type' => 'Bearer',
        ]),
    ]);

    $this->artisan('auth:login', ['code' => 'code123'])->assertSuccessful();

    expect(UserConfig::get(Setting::ClientId))->toBe('cid')
        ->and(UserConfig::get(Setting::ClientSecret))->toBe('secret')
        ->and(UserConfig::get(Setting::RedirectUri))->toBe('urn:ietf:wg:oauth:2.0:oob');
});
