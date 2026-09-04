<?php

use App\Enums\FetchPeriod;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-08-19 14:35:00'); // Wed
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('runs the current calendar week up to today', function () {
    $period = FetchPeriod::ThisWeek->toPeriodData();

    expect((string) $period)->toBe('2026-08-17 to 2026-08-19');
});

it('spans the full previous calendar week', function () {
    $period = FetchPeriod::LastWeek->toPeriodData();

    expect((string) $period)->toBe('2026-08-10 to 2026-08-16');
});

it('runs the current calendar month up to today', function () {
    $period = FetchPeriod::ThisMonth->toPeriodData();

    expect((string) $period)->toBe('2026-08-01 to 2026-08-19');
});

it('spans the full previous calendar month', function () {
    $period = FetchPeriod::LastMonth->toPeriodData();

    expect((string) $period)->toBe('2026-07-01 to 2026-07-31');
});

it('runs the current calendar year up to today', function () {
    $period = FetchPeriod::ThisYear->toPeriodData();

    expect((string) $period)->toBe('2026-01-01 to 2026-08-19');
});

it('spans the full previous calendar year', function () {
    $period = FetchPeriod::LastYear->toPeriodData();

    expect((string) $period)->toBe('2025-01-01 to 2025-12-31');
});

it('keeps the previous month intact when today has no counterpart in it', function () {
    CarbonImmutable::setTestNow('2026-03-31');

    $period = FetchPeriod::LastMonth->toPeriodData();

    expect((string) $period)->toBe('2026-02-01 to 2026-02-28');
});

it('strips the time of day from both boundaries', function () {
    $period = FetchPeriod::ThisWeek->toPeriodData();

    expect($period->since->format('H:i:s'))->toBe('00:00:00')
        ->and($period->until->format('H:i:s'))->toBe('00:00:00');
});

it('resolves every preset from its option value', function () {
    expect(FetchPeriod::values())->toBe([
        'this-week',
        'last-week',
        'this-month',
        'last-month',
        'this-year',
        'last-year',
    ]);

    foreach (FetchPeriod::values() as $value) {
        expect(FetchPeriod::tryFrom($value))->toBeInstanceOf(FetchPeriod::class);
    }
});

it('keeps the previous year intact when today has no counterpart in it', function () {
    CarbonImmutable::setTestNow('2028-02-29');

    $period = FetchPeriod::LastYear->toPeriodData();

    expect((string) $period)->toBe('2027-01-01 to 2027-12-31');
});
