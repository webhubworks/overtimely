<?php

use App\Enums\Setting;

afterEach(function () {
    withEnv(Setting::TableStyle, null);
});

it('gives every key a unique setting name', function () {
    $names = array_map(fn (Setting $key): string => $key->kebabName(), Setting::cases());

    expect($names)->toHaveCount(count(array_unique($names)));
});

it('resolves every key back from its setting name', function () {
    foreach (Setting::cases() as $key) {
        expect(Setting::fromKebabName($key->kebabName()))->toBe($key);
    }
});

it('resolves nothing from an unknown setting name', function () {
    expect(Setting::fromKebabName('nonsense'))->toBeNull()
        ->and(Setting::fromKebabName('timely.account.id'))->toBeNull();
});

it('agrees with itself on which keys are settable', function () {
    foreach (Setting::cases() as $key) {
        expect($key->isSettable())->toBe(in_array($key, Setting::settable(), true));
    }
});

it('keeps the tokens and the client secret out of listings', function () {
    $secrets = array_filter(Setting::cases(), fn (Setting $key): bool => $key->isSecret());

    expect(array_values($secrets))->toBe([
        Setting::AccessToken,
        Setting::RefreshToken,
        Setting::ClientSecret,
    ]);
});

it('never exposes a secret as settable', function () {
    foreach (Setting::settable() as $key) {
        expect($key->isSecret())->toBeFalse();
    }
});

it('labels every key', function () {
    foreach (Setting::cases() as $key) {
        expect($key->label())->not->toBeEmpty();
    }
});

it('reads a value from the environment', function () {
    withEnv(Setting::TableStyle, 'box');

    expect(Setting::TableStyle->envValue('default'))->toBe('box');
});

it('falls back to the default when the environment variable is blank', function () {
    withEnv(Setting::TableStyle, '');

    expect(Setting::TableStyle->envValue('default'))->toBe('default');
});

it('falls back to the default when the environment variable is absent', function () {
    withEnv(Setting::TableStyle, null);

    expect(Setting::TableStyle->envValue('default'))->toBe('default');
});

it('has no value of its own when the environment variable is blank and there is no default', function () {
    withEnv(Setting::TableStyle, '');

    expect(Setting::TableStyle->envValue())->toBeNull();
});
