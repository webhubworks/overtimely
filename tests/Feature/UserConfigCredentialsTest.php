<?php

use App\Support\UserConfig;

beforeEach(function () {
    config()->set('timely.refresh_token', null);
    config()->set('timely.account_id', null);
    config()->set('timely.user_id', null);
});

it('has credentials when refresh token, account and user are set', function () {
    config()->set('timely.refresh_token', 'tok');
    config()->set('timely.account_id', '1');
    config()->set('timely.user_id', '2');

    expect(UserConfig::hasCredentials())->toBeTrue();
});

it('has no credentials when one of them is missing', function (string $missing) {
    config()->set('timely.refresh_token', 'tok');
    config()->set('timely.account_id', '1');
    config()->set('timely.user_id', '2');
    config()->set($missing, null);

    expect(UserConfig::hasCredentials())->toBeFalse();
})->with([
    'timely.refresh_token',
    'timely.account_id',
    'timely.user_id',
]);

it('has no credentials when a value is an empty string', function () {
    config()->set('timely.refresh_token', '');
    config()->set('timely.account_id', '1');
    config()->set('timely.user_id', '2');

    expect(UserConfig::hasCredentials())->toBeFalse();
});
