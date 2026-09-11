<?php

use App\Data\PeriodData;
use App\Services\DailyTotalHoursService;
use Carbon\CarbonImmutable;

it('sums the logged hours over the period', function () {
    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    );

    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-06-01' => 8.0, // since
        '2026-06-02' => 7.5,
        '2026-06-03' => 6.0, // until
    ]), $period);

    // 8 + 7.5 + 6 = 21.5h, both boundary days included
    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(21.5);
});

it('ignores logged days outside the period', function () {
    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-02'),
    );

    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-05-31' => 9.0, // before since
        '2026-06-01' => 8.0,
        '2026-06-02' => 8.0,
        '2026-06-03' => 9.0, // after until
    ]), $period);

    // Only the two in-range days count => 16h
    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(16.0);
});

it('treats days with no logged entry as zero', function () {
    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    );

    // 06-02 is absent, as the API omits zero-hour days from the response.
    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-06-01' => 8.0,
        '2026-06-03' => 8.0,
    ]), $period);

    // 8 + 0 (gap on 06-02) + 8 = 16h
    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(16.0);
});

it('counts a single day', function () {
    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-01'),
    );

    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-06-01' => 8.0,
        '2026-06-02' => 8.0,
    ]), $period);

    // since == until => only 06-01
    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(8.0);
});

it('returns zero when no logged days fall in the period', function () {
    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    );

    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-05-01' => 8.0,
    ]), $period);

    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(0.0);
});

it('returns zero when a period outside the dataset period is requested', function () {
    $datasetPeriod = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-03'),
    );

    $period = PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-05'),
    );

    $service = DailyTotalHoursService::from(makeDailyLoggedHours([
        '2026-05-01' => 8.0,
    ]), $datasetPeriod);

    $logged = $service->forPeriod($period);

    expect($logged->totalHours)->toBe(0.0);
});
