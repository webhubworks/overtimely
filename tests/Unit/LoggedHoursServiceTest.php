<?php

use App\DataTransferObjects\PeriodData;
use App\Services\LoggedHoursService;
use Carbon\CarbonImmutable;

it('sums the logged hours over the period', function () {
    $service = LoggedHoursService::fromDailyDurations(makeDailyLoggedHours([
        '2026-06-01' => 8.0, // since
        '2026-06-02' => 7.5,
        '2026-06-03' => 6.0, // until
    ]));

    // 8 + 7.5 + 6 = 21.5h, both boundary days included
    $logged = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    ));

    expect($logged->totalHours)->toBe(21.5);
});

it('ignores logged days outside the period', function () {
    $service = LoggedHoursService::fromDailyDurations(makeDailyLoggedHours([
        '2026-05-31' => 9.0, // before since
        '2026-06-01' => 8.0,
        '2026-06-02' => 8.0,
        '2026-06-03' => 9.0, // after until
    ]));

    // Only the two in-range days count => 16h
    $logged = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-02'),
    ));

    expect($logged->totalHours)->toBe(16.0);
});

it('treats days with no logged entry as zero', function () {
    // 06-02 is absent, as the API omits zero-hour days from the response.
    $service = LoggedHoursService::fromDailyDurations(makeDailyLoggedHours([
        '2026-06-01' => 8.0,
        '2026-06-03' => 8.0,
    ]));

    // 8 + 0 (gap on 06-02) + 8 = 16h
    $logged = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    ));

    expect($logged->totalHours)->toBe(16.0);
});

it('counts a single day', function () {
    $service = LoggedHoursService::fromDailyDurations(makeDailyLoggedHours([
        '2026-06-01' => 8.0,
        '2026-06-02' => 8.0,
    ]));

    // since == until => only 06-01
    $logged = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-01'),
    ));

    expect($logged->totalHours)->toBe(8.0);
});

it('returns zero when no logged days fall in the period', function () {
    $service = LoggedHoursService::fromDailyDurations(makeDailyLoggedHours([
        '2026-05-01' => 8.0,
    ]));

    $logged = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    ));

    expect($logged->totalHours)->toBe(0.0);
});
