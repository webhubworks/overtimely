<?php

use App\Enums\ConfigKey;
use App\Support\UserConfig;

it('stores an account id given as an argument', function () {
    $this->artisan('config:set', ['setting' => 'account-id', 'value' => '4711'])
        ->expectsOutputToContain('Timely account ID set to 4711.')
        ->assertSuccessful();

    expect(UserConfig::get(ConfigKey::AccountId))->toBe(4711)
        ->and(ConfigKey::AccountId->getConfigValue())->toBe(4711);
});

it('rejects a non-numeric account id', function () {
    $this->artisan('config:set', ['setting' => 'account-id', 'value' => 'abc'])
        ->expectsOutputToContain('The account ID must be numeric.')
        ->assertFailed();

    expect(UserConfig::get(ConfigKey::AccountId))->toBeNull();
});

it('stores a report start date', function () {
    $this->artisan('config:set', ['setting' => 'since', 'value' => '2025-03-01'])
        ->assertSuccessful();

    expect(UserConfig::get(ConfigKey::Since))->toBe('2025-03-01');
});

it('rejects a report start date in the wrong format', function () {
    $this->artisan('config:set', ['setting' => 'since', 'value' => '01.03.2025'])
        ->expectsOutputToContain('Please use YYYY-MM-DD.')
        ->assertFailed();

    expect(UserConfig::get(ConfigKey::Since))->toBeNull();
});

it('stores a table style', function () {
    $this->artisan('config:set', ['setting' => 'table-style', 'value' => 'box-double'])
        ->assertSuccessful();

    expect(UserConfig::get(ConfigKey::TableStyle))->toBe('box-double');
});

it('rejects an unknown table style', function () {
    $this->artisan('config:set', ['setting' => 'table-style', 'value' => 'fancy'])
        ->expectsOutputToContain("Unknown table style 'fancy'.")
        ->assertFailed();

    expect(UserConfig::get(ConfigKey::TableStyle))->toBeNull();
});

it('rejects an unknown setting', function () {
    $this->artisan('config:set', ['setting' => 'nonsense', 'value' => 'x'])
        ->expectsOutputToContain("Unknown setting 'nonsense'.")
        ->assertFailed();
});

it('refuses to set a value the app manages itself', function () {
    $this->artisan('config:set', ['setting' => 'access-token', 'value' => 'x'])
        ->expectsOutputToContain('managed by the app')
        ->assertFailed();

    expect(UserConfig::get(ConfigKey::AccessToken))->toBeNull();
});

it('fails instead of prompting when it cannot ask for a setting', function () {
    $this->artisan('config:set', ['--no-interaction' => true])
        ->expectsOutputToContain('No setting given.')
        ->assertFailed();
});

it('fails instead of prompting when it cannot ask for a value', function () {
    $this->artisan('config:set', ['setting' => 'since', '--no-interaction' => true])
        ->expectsOutputToContain('No value given for the since setting.')
        ->assertFailed();
});
