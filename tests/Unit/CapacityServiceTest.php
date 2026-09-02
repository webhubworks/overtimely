<?php

use App\Data\PeriodData;
use App\Services\CapacityService;
use Carbon\CarbonImmutable;

it('sums the expected working hours over the period', function () {
    $service = new CapacityService(makeCapacity());

    // Mon 2025-07-07 ... Fri 2025-07-11 = 5 workdays x 8h = 40h expected
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-11'),
    ));

    expect($expected->totalHours)->toBe(40.0);
});

it('ignores days outside the capacity work days', function () {
    $service = CapacityService::fromCapacities(makeCapacity());

    // Fri ... Mon = 2 workdays (Sat/Sun excluded) => 16h expected
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2025-07-11'), // Fri
        CarbonImmutable::parse('2025-07-14'), // Mon
    ));

    expect($expected->totalHours)->toBe(16.0);
});

it('uses the latest-starting capacity that covers the day', function () {
    $service = CapacityService::fromCapacities(collect([
        makeCapacity(),                                             // open-ended default
        makeCapacity(dailyCapacity: 6.0, startDate: '2025-07-09'),  // takes over from Wed
    ]));

    // Mon,Tue @ 8h + Wed,Thu,Fri @ 6h = 16 + 18 = 34h
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-11'),
    ));

    expect($expected->totalHours)->toBe(34.0);
});

it('counts a single work day', function () {
    $service = CapacityService::fromCapacities(makeCapacity());

    // since == until, Monday
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2025-07-07'),
        CarbonImmutable::parse('2025-07-07'),
    ));

    expect($expected->totalHours)->toBe(8.0);
});

it('counts capacity for days in the future', function () {
    CarbonImmutable::setTestNow('2026-01-07'); // Wed

    $service = CapacityService::fromCapacities(makeCapacity());

    // Mon 2026-01-12 ... Fri 2026-01-16, entirely after today = 5 workdays x 8h
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-01-12'),
        CarbonImmutable::parse('2026-01-16'),
    ));

    expect($expected->totalHours)->toBe(40.0);
});

it('counts capacity across a period that spans today', function () {
    CarbonImmutable::setTestNow('2026-01-07'); // Wed

    $service = CapacityService::fromCapacities(makeCapacity());

    // Mon ... Fri around today = 5 workdays x 8h, not just the three up to today
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-01-05'),
        CarbonImmutable::parse('2026-01-09'),
    ));

    expect($expected->totalHours)->toBe(40.0);
});

it('stops counting at an explicit capacity end date', function () {
    CarbonImmutable::setTestNow('2026-01-07');

    $service = CapacityService::fromCapacities(makeCapacity(current: false, endDate: '2026-01-07'));

    // Mon,Tue,Wed @ 8h, then the capacity ends and Thu/Fri have none
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-01-05'),
        CarbonImmutable::parse('2026-01-09'),
    ));

    expect($expected->totalHours)->toBe(24.0);
});

it('uses the latest-starting capacity for days in the future', function () {
    CarbonImmutable::setTestNow('2026-01-07');

    $service = CapacityService::fromCapacities(collect([
        makeCapacity(),                                             // open-ended default
        makeCapacity(dailyCapacity: 6.0, startDate: '2026-01-12'),  // open-ended, takes over next week
    ]));

    // Mon 2026-01-12 ... Fri 2026-01-16 = 5 workdays x 6h
    $expected = $service->forPeriod(PeriodData::fromBoundaries(
        CarbonImmutable::parse('2026-01-12'),
        CarbonImmutable::parse('2026-01-16'),
    ));

    expect($expected->totalHours)->toBe(30.0);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});
