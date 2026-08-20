<?php

use App\Commands\Config\SetupCommand;
use App\Enums\ConfigKey;
use Illuminate\Support\Facades\Artisan;

it('names a registered command in every setup step', function () {
    $registered = array_keys(Artisan::all());

    foreach (SetupCommand::SETUP_STEPS as [$command, $arguments]) {
        expect($registered)->toContain($command);
    }
});

it('only asks config:set for settings that can be set', function () {
    foreach (SetupCommand::SETUP_STEPS as [$command, $arguments]) {
        if ($command !== 'config:set') {
            continue;
        }

        $key = ConfigKey::fromSettingName($arguments['setting']);

        expect($key)->not->toBeNull()
            ->and($key->isSettable())->toBeTrue();
    }
});

it('covers every settable setting', function () {
    $prompted = collect(SetupCommand::SETUP_STEPS)
        ->filter(fn (array $step): bool => $step[0] === 'config:set')
        ->map(fn (array $step): string => $step[1]['setting'])
        ->values()
        ->all();

    $settable = array_map(fn (ConfigKey $key): string => $key->settingName(), ConfigKey::settable());

    expect($prompted)->toEqualCanonicalizing($settable);
});

it('stops at the first step that fails', function () {
    $this->artisan('config:setup', ['--no-interaction' => true])
        ->expectsOutputToContain('Setup aborted at auth:login.')
        ->assertFailed();
});
