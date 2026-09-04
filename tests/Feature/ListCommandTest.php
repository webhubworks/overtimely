<?php

use App\Enums\Setting;
use App\Support\UserConfig;

it('lists every setting', function () {
    $command = $this->artisan('config:list');

    foreach (Setting::cases() as $key) {
        $command->expectsOutputToContain($key->kebabName());
    }

    $command->assertSuccessful();
});

it('masks secrets', function () {
    Setting::AccessToken->setConfigValue('plaintext-token');

    $this->artisan('config:list')
        ->expectsOutputToContain('********')
        ->doesntExpectOutputToContain('plaintext-token')
        ->assertSuccessful();
});

it('reports where each value came from', function () {
    UserConfig::set(Setting::AccountId, 4711);
    Setting::AccountId->setConfigValue(4711);

    $this->artisan('config:list')
        ->expectsOutputToContain('config file')
        ->assertSuccessful();
});

it('marks unset settings with a dash', function () {
    $this->artisan('config:list')
        ->expectsOutputToContain('—')
        ->assertSuccessful();
});
