<?php

use App\Enums\Setting;
use App\Support\UserConfig;

beforeEach(function () {
    Setting::RefreshToken->setConfigValue('token');
    Setting::AccountId->setConfigValue(1);
    Setting::UserId->setConfigValue(1);
    Setting::ClientId->setConfigValue(1);
    Setting::ClientSecret->setConfigValue('secret');
});

dataset('credentials', fn () => collect(Setting::credentials())
    ->mapWithKeys(fn (Setting $key): array => [$key->name => $key])
    ->all());

it('detects required credentials', function () {
    expect(UserConfig::isConfigured())->toBeTrue();
});

it('detects missing required credentials', function (Setting $missing) {
    $missing->setConfigValue(null);

    expect(UserConfig::isConfigured())->toBeFalse();
})->with('credentials');

it('detects missing required credentials when their values are empty strings', function (Setting $empty) {
    $empty->setConfigValue('');

    expect(UserConfig::isConfigured())->toBeFalse();
})->with('credentials');
