<?php

use App\Enums\ConfigKey;

afterEach(function () {
    withEnv(ConfigKey::TableStyle, null);
});

it('gives every key a unique setting name', function () {
    $names = array_map(fn (ConfigKey $key): string => $key->settingName(), ConfigKey::cases());

    expect($names)->toHaveCount(count(array_unique($names)));
});

it('resolves every key back from its setting name', function () {
    foreach (ConfigKey::cases() as $key) {
        expect(ConfigKey::fromSettingName($key->settingName()))->toBe($key);
    }
});

it('resolves nothing from an unknown setting name', function () {
    expect(ConfigKey::fromSettingName('nonsense'))->toBeNull()
        ->and(ConfigKey::fromSettingName('timely.account.id'))->toBeNull();
});

it('agrees with itself on which keys are settable', function () {
    foreach (ConfigKey::cases() as $key) {
        expect($key->isSettable())->toBe(in_array($key, ConfigKey::settable(), true));
    }
});

it('keeps the tokens and the client secret out of listings', function () {
    $secrets = array_filter(ConfigKey::cases(), fn (ConfigKey $key): bool => $key->isSecret());

    expect(array_values($secrets))->toBe([
        ConfigKey::AccessToken,
        ConfigKey::RefreshToken,
        ConfigKey::ClientSecret,
    ]);
});

it('never exposes a secret as settable', function () {
    foreach (ConfigKey::settable() as $key) {
        expect($key->isSecret())->toBeFalse();
    }
});

it('labels every key', function () {
    foreach (ConfigKey::cases() as $key) {
        expect($key->label())->not->toBeEmpty();
    }
});

it('reads a value from the environment', function () {
    withEnv(ConfigKey::TableStyle, 'box');

    expect(ConfigKey::TableStyle->envValue('default'))->toBe('box');
});

it('falls back to the default when the environment variable is blank', function () {
    withEnv(ConfigKey::TableStyle, '');

    expect(ConfigKey::TableStyle->envValue('default'))->toBe('default');
});

it('falls back to the default when the environment variable is absent', function () {
    withEnv(ConfigKey::TableStyle, null);

    expect(ConfigKey::TableStyle->envValue('default'))->toBe('default');
});

it('has no value of its own when the environment variable is blank and there is no default', function () {
    withEnv(ConfigKey::TableStyle, '');

    expect(ConfigKey::TableStyle->envValue())->toBeNull();
});
