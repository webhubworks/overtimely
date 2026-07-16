<?php

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

    expect(UserConfig::get(UserConfig::ACCESS_TOKEN))->toBe('at')
        ->and(UserConfig::get(UserConfig::REFRESH_TOKEN))->toBe('rt')
        ->and((int) UserConfig::get(UserConfig::TOKEN_EXPIRES_AT))->toBe(4600);
});

it('fails non-interactively when the oauth app is not configured', function () {
    config()->set('timely.oauth.client_id', null);
    config()->set('timely.oauth.client_secret', null);
    config()->set('timely.oauth.redirect_uri', null);

    $this->artisan('auth:login', ['code' => 'code123', '--no-interaction' => true])->assertFailed();
});
