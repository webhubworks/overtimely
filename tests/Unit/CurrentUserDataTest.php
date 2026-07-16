<?php

use App\DataTransferObjects\CurrentUserData;
use Carbon\CarbonImmutable;

it('builds from the api payload', function () {
    $user = CurrentUserData::fromApi(['id' => '7', 'created_at' => 1704067200]);

    expect($user->id)->toBe(7)
        ->and($user->createdAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->createdAt->timestamp)->toBe(1704067200);
});
