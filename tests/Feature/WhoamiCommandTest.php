<?php

use App\Enums\ConfigKey;
use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    ConfigKey::AccountId->setConfigValue('123');
    ConfigKey::AccessToken->setConfigValue('at');
});

it('fetches and stores the user id and account creation date', function () {
    Http::fake([
        '*/123/users/current' => Http::response([
            'id' => 42,
            'created_at' => 1704067200,
        ]),
    ]);

    $this->artisan('auth:whoami')->assertSuccessful();

    expect(UserConfig::get(ConfigKey::UserId))->toBe(42)
        ->and(UserConfig::get(ConfigKey::UserCreatedAt))
        ->toBe(CarbonImmutable::createFromTimestamp(1704067200)->format('Y-m-d'));
});

it('fails when no account id is set', function () {
    ConfigKey::AccountId->setConfigValue(null);

    $this->artisan('auth:whoami')->assertFailed();
});

it('fails when not authenticated', function () {
    ConfigKey::AccessToken->setConfigValue(null);
    ConfigKey::RefreshToken->setConfigValue(null);

    $this->artisan('auth:whoami')->assertFailed();
});
