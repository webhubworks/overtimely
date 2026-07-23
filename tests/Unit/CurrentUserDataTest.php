<?php

use App\DataTransferObjects\CurrentUserData;
use Carbon\CarbonImmutable;

it('builds from the api payload', function () {
    $user = new CurrentUserData(
        id: 7,
        createdAt: CarbonImmutable::createFromTimestamp(1704067200)
    );

    expect($user->id)->toBe(7)
        ->and($user->createdAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->createdAt->timestamp)->toBe(1704067200);
});
