<?php

use App\DataTransferObjects\PeriodData;
use App\Services\CapacityCalculationService;
use Carbon\CarbonImmutable;

it('sums the expected working hours over the period', function () {
    $service = new CapacityCalculationService(makeCapacity());

    // Mon 2025-07-07 ... Fri 2025-07-11 = 5 workdays x 8h = 40h expected
    $expected = $service->forPeriod(PeriodData::fromDates(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-11'),
    ));

    expect($expected->totalHours)->toBe(40.0);
});

it('ignores days outside the capacity work days', function () {
    $service = CapacityCalculationService::fromCapacities(makeCapacity());

    // Fri ... Mon = 2 workdays (Sat/Sun excluded) => 16h expected
    $expected = $service->forPeriod(PeriodData::fromDates(
        CarbonImmutable::parse('2025-07-11'), // Fri
        CarbonImmutable::parse('2025-07-14'), // Mon
    ));

    expect($expected->totalHours)->toBe(16.0);
});

it('uses the latest-starting capacity that covers the day', function () {
    $service = CapacityCalculationService::fromCapacities(collect([
        makeCapacity(),                                             // open-ended default
        makeCapacity(dailyCapacity: 6.0, startDate: '2025-07-09'),  // takes over from Wed
    ]));

    // Mon,Tue @ 8h + Wed,Thu,Fri @ 6h = 16 + 18 = 34h
    $expected = $service->forPeriod(PeriodData::fromDates(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-11'),
    ));

    expect($expected->totalHours)->toBe(34.0);
});

it('counts a single work day', function () {
    $service = CapacityCalculationService::fromCapacities(makeCapacity());

    // since == until, Monday
    $expected = $service->forPeriod(PeriodData::fromDates(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-07'),
    ));

    expect($expected->totalHours)->toBe(8.0);
});
