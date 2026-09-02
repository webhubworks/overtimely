<?php

use App\Data\OAuthTokenData;
use App\Enums\ConfigKey;
use App\Services\TimelyAuthService;
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
    ConfigKey::AccessToken->setConfigValue(null);
    ConfigKey::RefreshToken->setConfigValue(null);
    ConfigKey::TokenExpiresAt->setConfigValue(null);
});

it('builds the authorize url from config', function () {
    $url = app(TimelyAuthService::class)->authorizeUrl();

    expect($url)->toStartWith('https://timely.test/oauth/authorize?')
        ->toContain('response_type=code')
        ->toContain('client_id=cid')
        ->toContain('scope=manage')
        ->toContain('redirect_uri='.urlencode('urn:ietf:wg:oauth:2.0:oob'));
});

it('exchanges an authorization code for tokens', function () {
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

    $token = app(TimelyAuthService::class)->exchangeCode('code123');

    expect($token->accessToken)->toBe('at')
        ->and($token->refreshToken)->toBe('rt')
        ->and($token->expiresAt())->toBe(4600);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://timely.test/oauth/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'code123'
            && $request['client_id'] === 'cid'
            && $request['client_secret'] === 'secret'
            && $request['redirect_uri'] === 'urn:ietf:wg:oauth:2.0:oob';
    });
});

it('persists tokens, keeping the existing refresh token when not rotated', function () {
    UserConfig::set(ConfigKey::RefreshToken, 'old-refresh');

    app(TimelyAuthService::class)->persist(new OAuthTokenData(
        accessToken: 'new-access',
        refreshToken: null,
        expiresIn: 7200,
        createdAt: 2000,
        scope: 'manage',
        tokenType: 'Bearer',
    ));

    expect(UserConfig::get(ConfigKey::AccessToken))->toBe('new-access')
        ->and(UserConfig::get(ConfigKey::RefreshToken))->toBe('old-refresh')
        ->and((int) UserConfig::get(ConfigKey::TokenExpiresAt))->toBe(9200);
});

it('refreshes an expired access token and persists the result', function () {
    ConfigKey::AccessToken->setConfigValue('stale');
    ConfigKey::RefreshToken->setConfigValue('rt');
    ConfigKey::TokenExpiresAt->setConfigValue(100);

    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'fresh',
            'refresh_token' => 'rt2',
            'expires_in' => 3600,
            'created_at' => 5000,
            'scope' => 'manage',
            'token_type' => 'Bearer',
        ]),
    ]);

    $token = app(TimelyAuthService::class)->validAccessToken();

    expect($token)->toBe('fresh')
        ->and(ConfigKey::AccessToken->getConfigValue())->toBe('fresh')
        ->and(UserConfig::get(ConfigKey::AccessToken))->toBe('fresh')
        ->and(UserConfig::get(ConfigKey::RefreshToken))->toBe('rt2');

    Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'rt');
});

it('returns the stored token while it is still valid', function () {
    ConfigKey::AccessToken->setConfigValue('good');
    ConfigKey::RefreshToken->setConfigValue('rt');
    ConfigKey::TokenExpiresAt->setConfigValue(now()->timestamp + 100000);

    Http::fake();

    expect(app(TimelyAuthService::class)->validAccessToken())->toBe('good');

    Http::assertNothingSent();
});

it('returns the stored token when it never expires', function () {
    ConfigKey::AccessToken->setConfigValue('forever');
    ConfigKey::TokenExpiresAt->setConfigValue(null);

    Http::fake();

    expect(app(TimelyAuthService::class)->validAccessToken())->toBe('forever');

    Http::assertNothingSent();
});

it('throws when no tokens are present', function () {
    app(TimelyAuthService::class)->validAccessToken();
})->throws(RuntimeException::class);

it('does not treat an empty expiry as an expired token', function () {
    ConfigKey::AccessToken->setConfigValue('good');
    ConfigKey::RefreshToken->setConfigValue('rt');
    ConfigKey::TokenExpiresAt->setConfigValue('');

    Http::fake();

    expect(app(TimelyAuthService::class)->validAccessToken())->toBe('good');

    Http::assertNothingSent();
});

it('treats empty credentials as no tokens at all', function () {
    ConfigKey::AccessToken->setConfigValue('');
    ConfigKey::RefreshToken->setConfigValue('');
    ConfigKey::TokenExpiresAt->setConfigValue('');

    app(TimelyAuthService::class)->validAccessToken();
})->throws(RuntimeException::class, 'Not authenticated with Timely. Run auth:login first.');
