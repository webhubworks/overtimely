<?php

use App\DataTransferObjects\OAuthTokenData;

it('computes the absolute expiry from created_at plus expires_in', function () {
    $token = new OAuthTokenData('at', 'rt', 3600, 1000, 'manage', 'Bearer');

    expect($token->expiresAt())->toBe(4600);
});

it('has no expiry when expires_in is null', function () {
    $token = new OAuthTokenData('at', 'rt', null, 1000, 'manage', 'Bearer');

    expect($token->expiresAt())->toBeNull();
});
