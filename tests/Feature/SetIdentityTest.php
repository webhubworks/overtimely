<?php

use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    putenv('XDG_CONFIG_HOME='.sys_get_temp_dir().'/overtimely-test-'.uniqid('', true));

    config()->set('timely.account_id', '123');
    config()->set('timely.access_token', 'at');
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

it('fetches and stores the user id and account creation date', function () {
    Http::fake([
        '*/123/users/current' => Http::response([
            'id' => 42,
            'created_at' => 1704067200,
        ]),
    ]);

    $this->artisan('set:identity')->assertSuccessful();

    expect(UserConfig::get(UserConfig::USER_ID))->toBe('42')
        ->and(UserConfig::get(UserConfig::CREATED_AT))
        ->toBe(CarbonImmutable::createFromTimestamp(1704067200)->format('Y-m-d'));
});

it('fails when no account id is set', function () {
    config()->set('timely.account_id', null);

    $this->artisan('set:identity')->assertFailed();
});

it('fails when not authenticated', function () {
    config()->set('timely.access_token', null);
    config()->set('timely.refresh_token', null);

    $this->artisan('set:identity')->assertFailed();
});
