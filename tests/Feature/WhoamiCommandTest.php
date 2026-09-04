<?php

use App\Enums\Setting;
use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Setting::AccountId->setConfigValue('123');
    Setting::AccessToken->setConfigValue('at');
});

it('fetches and stores the user id and account creation date', function () {
    Http::fake([
        '*/123/users/current' => Http::response([
            'id' => 42,
            'created_at' => 1704067200,
        ]),
    ]);

    $this->artisan('auth:whoami')->assertSuccessful();

    expect(UserConfig::get(Setting::UserId))->toBe(42)
        ->and(UserConfig::get(Setting::UserCreatedAt))
        ->toBe(CarbonImmutable::createFromTimestamp(1704067200)->format('Y-m-d'));
});

it('fails when no account id is set', function () {
    Setting::AccountId->setConfigValue(null);

    $this->artisan('auth:whoami')->assertFailed();
});

it('fails when not authenticated', function () {
    Setting::AccessToken->setConfigValue(null);
    Setting::RefreshToken->setConfigValue(null);

    $this->artisan('auth:whoami')->assertFailed();
});
