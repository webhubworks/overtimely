<?php

use App\Enums\ConfigKey;
use App\Support\UserConfig;

beforeEach(function () {
    ConfigKey::RefreshToken->setConfigValue('token');
    ConfigKey::AccountId->setConfigValue(1);
    ConfigKey::UserId->setConfigValue(1);
    ConfigKey::ClientId->setConfigValue(1);
    ConfigKey::ClientSecret->setConfigValue('secret');
});

dataset('credentials', fn () => collect(ConfigKey::credentials())
    ->mapWithKeys(fn (ConfigKey $key): array => [$key->name => $key])
    ->all());

it('detects required credentials', function () {
    expect(UserConfig::isConfigured())->toBeTrue();
});

it('detects missing required credentials', function (ConfigKey $missing) {
    $missing->setConfigValue(null);

    expect(UserConfig::isConfigured())->toBeFalse();
})->with('credentials');

it('detects missing required credentials when their values are empty strings', function (ConfigKey $empty) {
    $empty->setConfigValue('');

    expect(UserConfig::isConfigured())->toBeFalse();
})->with('credentials');
