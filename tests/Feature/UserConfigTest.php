<?php

use App\Enums\ConfigKey;
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
    UserConfig::set(ConfigKey::AccountId, '123');

    expect(UserConfig::get(ConfigKey::AccountId))->toBe('123');
});

it('returns null for an unset key', function () {
    expect(UserConfig::get(ConfigKey::Since))->toBeNull();
});

it('treats an empty string as null', function () {
    UserConfig::set(ConfigKey::Since, '');

    expect(UserConfig::get(ConfigKey::Since))->toBeNull();
});

it('unsets a key when set to null', function () {
    UserConfig::set(ConfigKey::UserId, '42');
    UserConfig::set(ConfigKey::UserId, null);

    expect(UserConfig::get(ConfigKey::UserId))->toBeNull();
});

it('writes many keys in a single call', function () {
    UserConfig::setMany([
        [ConfigKey::RefreshToken, 'tok'],
        [ConfigKey::AccountId, '1'],
        [ConfigKey::UserId, '2'],
    ]);

    expect(UserConfig::get(ConfigKey::RefreshToken))->toBe('tok')
        ->and(UserConfig::get(ConfigKey::AccountId))->toBe('1')
        ->and(UserConfig::get(ConfigKey::UserId))->toBe('2');
});

it('is configured only when every credential is present in the file', function () {
    expect(UserConfig::isConfigured())->toBeFalse();

    UserConfig::setMany([
        [ConfigKey::RefreshToken, 'tok'],
        [ConfigKey::AccountId, '1'],
        [ConfigKey::UserId, '2'],
    ]);

    expect(UserConfig::isConfigured())->toBeFalse();

    UserConfig::setMany([
        [ConfigKey::OAuthClientId, 'cid'],
        [ConfigKey::OAuthClientSecret, 'secret'],
    ]);

    expect(UserConfig::isConfigured())->toBeTrue();
});

it('mirrors the config repository structure on disk', function () {
    UserConfig::setMany([
        [ConfigKey::OAuthClientId, 'abc'],
        [ConfigKey::TableStyle, 'box'],
    ]);

    expect(json_decode(file_get_contents(UserConfig::path()), true))->toBe([
        'timely' => ['oauth' => ['client_id' => 'abc']],
        'display' => ['table_style' => 'box'],
    ]);
});

it('drops settings left behind by an older key layout', function () {
    UserConfig::save(['account_id' => '999', 'timely' => ['account_id' => '1']]);

    expect(UserConfig::load())->toBe(['timely' => ['account_id' => '1']]);
});

it('maps every config key to a real config entry', function () {
    foreach (ConfigKey::cases() as $key) {
        expect(config()->has($key->value))->toBeTrue("missing config entry for {$key->name}");
    }
});
