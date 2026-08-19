<?php

use App\Enums\ReportPeriod;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-08-19 14:35:00'); // Wed
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('runs the current calendar week up to today', function () {
    $period = ReportPeriod::ThisWeek->toPeriodData();

    expect((string) $period)->toBe('2026-08-17 to 2026-08-19');
});

it('spans the full previous calendar week', function () {
    $period = ReportPeriod::LastWeek->toPeriodData();

    expect((string) $period)->toBe('2026-08-10 to 2026-08-16');
});

it('runs the current calendar month up to today', function () {
    $period = ReportPeriod::ThisMonth->toPeriodData();

    expect((string) $period)->toBe('2026-08-01 to 2026-08-19');
});

it('spans the full previous calendar month', function () {
    $period = ReportPeriod::LastMonth->toPeriodData();

    expect((string) $period)->toBe('2026-07-01 to 2026-07-31');
});

it('runs the current calendar year up to today', function () {
    $period = ReportPeriod::ThisYear->toPeriodData();

    expect((string) $period)->toBe('2026-01-01 to 2026-08-19');
});

it('spans the full previous calendar year', function () {
    $period = ReportPeriod::LastYear->toPeriodData();

    expect((string) $period)->toBe('2025-01-01 to 2025-12-31');
});

it('keeps the previous month intact when today has no counterpart in it', function () {
    CarbonImmutable::setTestNow('2026-03-31');

    $period = ReportPeriod::LastMonth->toPeriodData();

    expect((string) $period)->toBe('2026-02-01 to 2026-02-28');
});

it('strips the time of day from both boundaries', function () {
    $period = ReportPeriod::ThisWeek->toPeriodData();

    expect($period->since->format('H:i:s'))->toBe('00:00:00')
        ->and($period->until->format('H:i:s'))->toBe('00:00:00');
});

it('resolves every preset from its option value', function () {
    expect(ReportPeriod::values())->toBe([
        'this-week',
        'last-week',
        'this-month',
        'last-month',
        'this-year',
        'last-year',
    ]);

    foreach (ReportPeriod::values() as $value) {
        expect(ReportPeriod::tryFrom($value))->toBeInstanceOf(ReportPeriod::class);
    }
});

it('keeps the previous year intact when today has no counterpart in it', function () {
    CarbonImmutable::setTestNow('2028-02-29');

    $period = ReportPeriod::LastYear->toPeriodData();

    expect((string) $period)->toBe('2027-01-01 to 2027-12-31');
});
