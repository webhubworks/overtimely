<?php

use App\DataTransferObjects\DurationData;

it('decomposes hour totals into h/m/s', function () {
    $duration = DurationData::fromTotalHours(45.5);

    expect($duration->hours)->toBe(45)
        ->and($duration->minutes)->toBe(30)
        ->and($duration->seconds)->toBe(0)
        ->and($duration->toString())->toBe('45h 30m');
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
        ->and($duration->toString())->toBe('-2h 30m');
});

it('pads both components in the tabular format', function () {
    expect(DurationData::fromTotalHours(8)->toString(tabular: true))->toBe('08h 00m')
        ->and(DurationData::fromTotalHours(0.4)->toString(tabular: true))->toBe('00h 24m')
        ->and(DurationData::fromTotalHours(145.7)->toString(tabular: true))->toBe('145h 42m');
});

it('keeps the minus sign on sub-hour negative durations in the tabular format', function () {
    $duration = DurationData::fromTotalHours(-32 / 60);

    expect($duration->toString(tabular: true))->toBe('-00h 32m')
        ->and($duration->toString(prefixPositive: true, tabular: true))->toBe('-00h 32m');
});

it('signs the tabular format from the total, not from the hour component', function () {
    expect(DurationData::fromTotalHours(-1.5)->toString(prefixPositive: true, tabular: true))->toBe('-01h 30m')
        ->and(DurationData::fromTotalHours(8.75)->toString(prefixPositive: true, tabular: true))->toBe('+08h 45m')
        ->and(DurationData::fromTotalHours(8.75)->toString(tabular: true))->toBe('08h 45m');
});

it('renders a zero duration as the fallback string', function () {
    expect(DurationData::fromTotalHours(0)->toString())->toBe('—')
        ->and(DurationData::fromTotalHours(0)->toString(fallback: 'N/A'))->toBe('N/A');
});
