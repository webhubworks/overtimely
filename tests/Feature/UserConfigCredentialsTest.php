<?php

use App\Enums\ConfigKey;
use App\Support\UserConfig;

beforeEach(function () {
    ConfigKey::RefreshToken->setConfigValue('token');
    ConfigKey::AccountId->setConfigValue(1);
    ConfigKey::UserId->setConfigValue(1);
    ConfigKey::OAuthClientId->setConfigValue(1);
    ConfigKey::OAuthClientSecret->setConfigValue('secret');
});

dataset('credentials', fn () => collect(ConfigKey::credentials())
    ->mapWithKeys(fn (ConfigKey $key): array => [$key->name => $key])
    ->all());

it('detects required credentials', function () {
    expect(UserConfig::hasCredentials())->toBeTrue();
});

it('detects missing required credentials', function (ConfigKey $missing) {
    $missing->setConfigValue(null);

    expect(UserConfig::hasCredentials())->toBeFalse();
})->with('credentials');

it('has no credentials when a value is an empty string', function (ConfigKey $empty) {
    $empty->setConfigValue('');

    expect(UserConfig::hasCredentials())->toBeFalse();
})->with('credentials');
