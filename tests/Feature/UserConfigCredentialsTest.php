<?php

use App\Support\UserConfig;

beforeEach(function () {
    config()->set('timely.refresh_token', 'token');
    config()->set('timely.account_id', 1);
    config()->set('timely.user_id', 1);
    config()->set('timely.oauth.client_id', 1);
    config()->set('timely.oauth.client_secret', 'secret');
});

it('detects required credentials', function () {
    expect(UserConfig::hasCredentials())->toBeTrue();
});

it('detects missing required credentials', function (string $missing) {
    config()->set($missing, null);

    expect(UserConfig::hasCredentials())->toBeFalse();
})->with([
    'timely.refresh_token',
    'timely.account_id',
    'timely.user_id',
    'timely.oauth.client_id',
    'timely.oauth.client_secret',
]);

it('has no credentials when a value is an empty string', function (string $empty) {
    config()->set($empty, null);

    expect(UserConfig::hasCredentials())->toBeFalse();
})->with([
    'timely.refresh_token',
    'timely.account_id',
    'timely.user_id',
    'timely.oauth.client_id',
    'timely.oauth.client_secret',
]);
