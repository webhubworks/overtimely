<?php

use App\Enums\ConfigKey;

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
