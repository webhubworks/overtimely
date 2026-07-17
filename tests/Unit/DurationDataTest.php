<?php

use App\DataTransferObjects\DurationData;

it('decomposes hour totals into h/m/s', function () {
    $duration = DurationData::fromTotalHours(45.5);

    expect($duration->hours)->toBe(45)
        ->and($duration->minutes)->toBe(30)
        ->and($duration->seconds)->toBe(0)
        ->and($duration->readable())->toBe('45h 30m');
});

it('rounds to the nearest second instead of truncating float error', function () {
    // 8h that arrived as 7.999999999999998 from a float subtraction
    $duration = DurationData::fromTotalHours(7.999999999999998);

    expect($duration->hours)->toBe(8)
        ->and($duration->minutes)->toBe(0)
        ->and($duration->seconds)->toBe(0);
});

it('carries the sign for negative balances', function () {
    $duration = DurationData::fromTotalHours(-2.5);

    expect($duration->hours)->toBe(2) // Only magnitude in the components
        ->and($duration->minutes)->toBe(30) // Only magnitude in the components
        ->and($duration->totalSeconds)->toBe(-9000) // Sign in the totals
        ->and($duration->readable())->toBe('-2h 30m');
});
