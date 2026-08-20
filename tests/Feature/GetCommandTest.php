<?php

use App\Enums\ConfigKey;

it('prints the value of a setting', function () {
    ConfigKey::AccountId->setConfigValue(967619);

    $this->artisan('config:get', ['setting' => 'account-id'])
        ->expectsOutputToContain('967619')
        ->assertSuccessful();
});

it('prints secrets in full', function () {
    ConfigKey::AccessToken->setConfigValue('plaintext-token');

    $this->artisan('config:get', ['setting' => 'access-token'])
        ->expectsOutputToContain('plaintext-token')
        ->assertSuccessful();
});

it('fails for an unknown setting', function () {
    $this->artisan('config:get', ['setting' => 'nonsense'])
        ->expectsOutputToContain("Unknown setting 'nonsense'.")
        ->assertFailed();
});

it('fails when the setting holds no value', function () {
    $this->artisan('config:get', ['setting' => 'since'])
        ->expectsOutputToContain('The since setting is not set.')
        ->assertFailed();
});
