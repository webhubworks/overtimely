<?php

use App\Enums\Setting;
use App\Support\UserConfig;

it('round-trips a value through set and get', function () {
    UserConfig::set(Setting::AccountId, '123');

    expect(UserConfig::get(Setting::AccountId))->toBe('123');
});

it('returns null for an unset key', function () {
    expect(UserConfig::get(Setting::ReportSince))->toBeNull();
});

it('treats an empty string as null', function () {
    UserConfig::set(Setting::ReportSince, '');

    expect(UserConfig::get(Setting::ReportSince))->toBeNull();
});

it('unsets a key when set to null', function () {
    UserConfig::set(Setting::UserId, '42');
    UserConfig::set(Setting::UserId, null);

    expect(UserConfig::get(Setting::UserId))->toBeNull();
});

it('writes many keys in a single call', function () {
    UserConfig::setMany([
        [Setting::RefreshToken, 'tok'],
        [Setting::AccountId, '1'],
        [Setting::UserId, '2'],
    ]);

    expect(UserConfig::get(Setting::RefreshToken))->toBe('tok')
        ->and(UserConfig::get(Setting::AccountId))->toBe('1')
        ->and(UserConfig::get(Setting::UserId))->toBe('2');
});

it('mirrors the config repository structure on disk', function () {
    UserConfig::setMany([
        [Setting::ClientId, 'abc'],
        [Setting::AccessToken, 'at'],
        [Setting::RefreshToken, 'rt'],
        [Setting::TokenExpiresAt, 4600],
        [Setting::AccountId, 123],
        [Setting::UserId, 42],
        [Setting::UserCreatedAt, '2024-01-01'],
        [Setting::ReportFetchMode, 'events'],
        [Setting::ReportSince, '2025-01-01'],
        [Setting::ReportUntil, '2025-12-31'],
        [Setting::TableStyle, 'box'],
    ]);

    expect(json_decode(file_get_contents(UserConfig::path()), true))->toBe([
        'timely' => [
            'oauth' => ['client_id' => 'abc'],
            'tokens' => ['access' => 'at', 'refresh' => 'rt', 'expires_at' => 4600],
            'account' => ['id' => 123],
            'user' => ['id' => 42, 'created_at' => '2024-01-01'],
            'report' => ['fetch_mode' => 'events', 'since' => '2025-01-01', 'until' => '2025-12-31'],
        ],
        'display' => ['table_style' => 'box'],
    ]);
});

it('drops settings left behind by an older key layout', function () {
    UserConfig::save(['account_id' => '999', 'timely' => ['account_id' => '1']]);

    expect(UserConfig::load())->toBe(['timely' => ['account_id' => '1']]);
});

it('maps every config key to a real config entry', function () {
    foreach (Setting::cases() as $key) {
        expect(config()->has($key->value))->toBeTrue("missing config entry for {$key->name}");
    }
});
