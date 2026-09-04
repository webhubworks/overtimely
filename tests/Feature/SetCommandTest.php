<?php

use App\Enums\Setting;
use App\Support\UserConfig;

it('stores an account id given as an argument', function () {
    $this->artisan('config:set', ['setting' => 'account-id', 'value' => '4711'])
        ->expectsOutputToContain('Timely account ID set to 4711.')
        ->assertSuccessful();

    expect(UserConfig::get(Setting::AccountId))->toBe(4711)
        ->and(Setting::AccountId->getConfigValue())->toBe(4711);
});

it('rejects a non-numeric account id', function () {
    $this->artisan('config:set', ['setting' => 'account-id', 'value' => 'abc'])
        ->expectsOutputToContain('The account ID must be numeric.')
        ->assertFailed();

    expect(UserConfig::get(Setting::AccountId))->toBeNull();
});

it('stores a report start date', function () {
    $this->artisan('config:set', ['setting' => 'report-since', 'value' => '2025-03-01'])
        ->assertSuccessful();

    expect(UserConfig::get(Setting::ReportSince))->toBe('2025-03-01');
});

it('stores a relative report start date', function () {
    $this->artisan('config:set', ['setting' => 'report-since', 'value' => 'first day of this month'])
        ->assertSuccessful();

    expect(UserConfig::get(Setting::ReportSince))->toBe('first day of this month');
});

it('rejects an unparsable report start date', function () {
    $this->artisan('config:set', ['setting' => 'report-since', 'value' => 'garbage-input'])
        ->expectsOutputToContain('Unsupported format.')
        ->assertFailed();

    expect(UserConfig::get(Setting::ReportSince))->toBeNull();
});

it('stores a table style', function () {
    $this->artisan('config:set', ['setting' => 'table-style', 'value' => 'box-double'])
        ->assertSuccessful();

    expect(UserConfig::get(Setting::TableStyle))->toBe('box-double');
});

it('rejects an unknown table style', function () {
    $this->artisan('config:set', ['setting' => 'table-style', 'value' => 'fancy'])
        ->expectsOutputToContain("Unknown table style 'fancy'.")
        ->assertFailed();

    expect(UserConfig::get(Setting::TableStyle))->toBeNull();
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

    expect(UserConfig::get(Setting::AccessToken))->toBeNull();
});

it('fails instead of prompting when it cannot ask for a setting', function () {
    $this->artisan('config:set', ['--no-interaction' => true])
        ->expectsOutputToContain('No setting given.')
        ->assertFailed();
});

it('fails instead of prompting when it cannot ask for a value', function () {
    $this->artisan('config:set', ['setting' => 'report-since', '--no-interaction' => true])
        ->expectsOutputToContain('No value given for the report-since setting.')
        ->assertFailed();
});
