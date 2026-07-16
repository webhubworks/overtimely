<?php

use App\DataTransferObjects\OAuthTokenData;
use App\Services\TimelyAuth;
use App\Support\UserConfig;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    putenv('XDG_CONFIG_HOME='.sys_get_temp_dir().'/overtimely-test-'.uniqid('', true));

    config()->set('timely.oauth', [
        'authorize_url' => 'https://timely.test/oauth/authorize',
        'token_url' => 'https://timely.test/oauth/token',
        'client_id' => 'cid',
        'client_secret' => 'secret',
        'scope' => 'manage',
        'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
    ]);
    config()->set('timely.access_token', null);
    config()->set('timely.refresh_token', null);
    config()->set('timely.token_expires_at', null);
});

afterEach(function () {
    $file = UserConfig::path();
    if (is_file($file)) {
        unlink($file);
    }

    $dir = dirname($file);
    if (is_dir($dir)) {
        rmdir($dir);
    }

    $home = getenv('XDG_CONFIG_HOME');
    if (is_string($home) && is_dir($home)) {
        rmdir($home);
    }

    putenv('XDG_CONFIG_HOME');
});

it('builds the authorize url from config', function () {
    $url = app(TimelyAuth::class)->authorizeUrl();

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

    $token = app(TimelyAuth::class)->exchangeCode('code123');

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
    UserConfig::set(UserConfig::REFRESH_TOKEN, 'old-refresh');

    app(TimelyAuth::class)->persist(new OAuthTokenData(
        accessToken: 'new-access',
        refreshToken: null,
        expiresIn: 7200,
        createdAt: 2000,
        scope: 'manage',
        tokenType: 'Bearer',
    ));

    expect(UserConfig::get(UserConfig::ACCESS_TOKEN))->toBe('new-access')
        ->and(UserConfig::get(UserConfig::REFRESH_TOKEN))->toBe('old-refresh')
        ->and((int) UserConfig::get(UserConfig::TOKEN_EXPIRES_AT))->toBe(9200);
});

it('refreshes an expired access token and persists the result', function () {
    config()->set('timely.access_token', 'stale');
    config()->set('timely.refresh_token', 'rt');
    config()->set('timely.token_expires_at', 100);

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

    $token = app(TimelyAuth::class)->validAccessToken();

    expect($token)->toBe('fresh')
        ->and(config('timely.access_token'))->toBe('fresh')
        ->and(UserConfig::get(UserConfig::ACCESS_TOKEN))->toBe('fresh')
        ->and(UserConfig::get(UserConfig::REFRESH_TOKEN))->toBe('rt2');

    Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'rt');
});

it('returns the stored token while it is still valid', function () {
    config()->set('timely.access_token', 'good');
    config()->set('timely.refresh_token', 'rt');
    config()->set('timely.token_expires_at', now()->timestamp + 100000);

    Http::fake();

    expect(app(TimelyAuth::class)->validAccessToken())->toBe('good');

    Http::assertNothingSent();
});

it('returns the stored token when it never expires', function () {
    config()->set('timely.access_token', 'forever');
    config()->set('timely.token_expires_at', null);

    Http::fake();

    expect(app(TimelyAuth::class)->validAccessToken())->toBe('forever');

    Http::assertNothingSent();
});

it('throws when no tokens are present', function () {
    app(TimelyAuth::class)->validAccessToken();
})->throws(RuntimeException::class);
