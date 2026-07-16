<?php

use App\Support\UserConfig;

beforeEach(function () {
    putenv('XDG_CONFIG_HOME='.sys_get_temp_dir().'/overtimely-test-'.uniqid('', true));
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

it('round-trips a value through set and get', function () {
    UserConfig::set(UserConfig::ACCOUNT_ID, '123');

    expect(UserConfig::get(UserConfig::ACCOUNT_ID))->toBe('123');
});

it('returns null for an unset key', function () {
    expect(UserConfig::get(UserConfig::SINCE))->toBeNull();
});

it('treats an empty string as null', function () {
    UserConfig::set(UserConfig::SINCE, '');

    expect(UserConfig::get(UserConfig::SINCE))->toBeNull();
});

it('unsets a key when set to null', function () {
    UserConfig::set(UserConfig::USER_ID, '42');
    UserConfig::set(UserConfig::USER_ID, null);

    expect(UserConfig::get(UserConfig::USER_ID))->toBeNull();
});

it('writes many keys in a single call', function () {
    UserConfig::setMany([
        UserConfig::REFRESH_TOKEN => 'tok',
        UserConfig::ACCOUNT_ID => '1',
        UserConfig::USER_ID => '2',
    ]);

    expect(UserConfig::get(UserConfig::REFRESH_TOKEN))->toBe('tok')
        ->and(UserConfig::get(UserConfig::ACCOUNT_ID))->toBe('1')
        ->and(UserConfig::get(UserConfig::USER_ID))->toBe('2');
});

it('throws on an unknown key', function () {
    UserConfig::get('nope');
})->throws(InvalidArgumentException::class);

it('is configured only when refresh token, account and user are set', function () {
    expect(UserConfig::isConfigured())->toBeFalse();

    UserConfig::setMany([
        UserConfig::REFRESH_TOKEN => 'tok',
        UserConfig::ACCOUNT_ID => '1',
        UserConfig::USER_ID => '2',
    ]);

    expect(UserConfig::isConfigured())->toBeTrue();
});
