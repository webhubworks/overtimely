<?php

use App\Enums\ConfigKey;
use App\Support\UserConfig;

it('lists every setting', function () {
    $command = $this->artisan('config:list');

    foreach (ConfigKey::cases() as $key) {
        $command->expectsOutputToContain($key->settingName());
    }

    $command->assertSuccessful();
});

it('masks secrets', function () {
    ConfigKey::AccessToken->setConfigValue('plaintext-token');

    $this->artisan('config:list')
        ->expectsOutputToContain('********')
        ->doesntExpectOutputToContain('plaintext-token')
        ->assertSuccessful();
});

it('reports where each value came from', function () {
    forgetSetting(ConfigKey::AccountId, ConfigKey::Since);

    UserConfig::set(ConfigKey::AccountId, 4711);
    ConfigKey::AccountId->setConfigValue(4711);

    $this->artisan('config:list')
        ->expectsOutputToContain('config file')
        ->assertSuccessful();
});

it('marks unset settings with a dash', function () {
    forgetSetting(ConfigKey::Since);

    $this->artisan('config:list')
        ->expectsOutputToContain('—')
        ->assertSuccessful();
});
