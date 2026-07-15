<?php

use App\DataTransferObjects\DurationData;

it('decomposes hour totals into h/m/s', function () {
    $duration = DurationData::fromTotalHours(45.5);

    expect($duration->hours)->toBe(45)
        ->and($duration->minutes)->toBe(30)
        ->and($duration->seconds)->toBe(0)
        ->and($duration->formatted)->toBe('45:30');
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

    expect($duration->hours)->toBe(2)            // magnitude in the components
        ->and($duration->minutes)->toBe(30)
        ->and($duration->totalSeconds)->toBe(-9000) // sign in the totals
        ->and($duration->formatted)->toBe('-02:30');
});
