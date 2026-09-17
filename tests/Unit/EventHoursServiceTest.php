<?php

use App\Data\EventData;
use App\Data\PeriodData;
use App\Services\EventHoursService;

/**
 * @param  array<int, EventData>  $events
 */
function makeEventHours(array $events, PeriodData $datasetPeriod): EventHoursService
{
    return EventHoursService::from([$events], $datasetPeriod);
}

function day(string $day): PeriodData
{
    return makePeriod($day, $day);
}

it('merges parallel timestamps instead of summing them', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    // 09:00-11:00 and 10:00-12:00 cover 09:00-12:00, so 3h worked, not 4h logged
    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 11:00']]),
        makeEvent('2026-06-01', [['2026-06-01 10:00', '2026-06-01 12:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0);
});

it('keeps a nested timestamp from shortening the span it sits in', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 12:00']]),
        makeEvent('2026-06-01', [['2026-06-01 10:00', '2026-06-01 10:30']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0);
});

it('counts timestamps that only touch as their full sum', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 10:00']]),
        makeEvent('2026-06-01', [['2026-06-01 10:00', '2026-06-01 11:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(2.0);
});

it('sums timestamps that do not overlap', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 10:00']]),
        makeEvent('2026-06-01', [['2026-06-01 13:00', '2026-06-01 14:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(2.0);
});

it('merges every timestamp of a single event', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [
            ['2026-06-01 09:00', '2026-06-01 11:00'],
            ['2026-06-01 10:00', '2026-06-01 12:00'],
        ]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0);
});

it('merges parallel work that runs over midnight', function () {
    $period = makePeriod('2026-06-01', '2026-06-02');

    // 23:00-01:00 beside 00:30-02:00 covers 23:00-02:00, so 3h and not 3.5h
    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 23:00', '2026-06-02 01:00']]),
        makeEvent('2026-06-02', [['2026-06-02 00:30', '2026-06-02 02:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0)
        ->and($service->forPeriod(day('2026-06-01'))->totalHours)->toBe(1.0)
        ->and($service->forPeriod(day('2026-06-02'))->totalHours)->toBe(2.0);
});

it('books each day of a timer left running over several midnights', function () {
    $period = makePeriod('2026-06-01', '2026-06-04');

    // 06-01 22:00 to 06-04 03:00 is 53h: 2h + 24h + 24h + 3h
    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 22:00', '2026-06-04 03:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(53.0)
        ->and($service->forPeriod(day('2026-06-01'))->totalHours)->toBe(2.0)
        ->and($service->forPeriod(day('2026-06-02'))->totalHours)->toBe(24.0)
        ->and($service->forPeriod(day('2026-06-04'))->totalHours)->toBe(3.0);
});

it('books a timestamp on the day its own offset puts it on', function () {
    $period = makePeriod('2026-06-01', '2026-06-02');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01T23:30:00+02:00', '2026-06-02T00:30:00+02:00']]),
    ], $period);

    expect($service->forPeriod(day('2026-06-01'))->totalHours)->toBe(0.5)
        ->and($service->forPeriod(day('2026-06-02'))->totalHours)->toBe(0.5);
});

it('falls back to the event duration when it has no timestamps', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', hours: 3.0),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0);
});

it('adds an untimed event on top of the merged timestamps of the same day', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    // an untimed entry has no position on the clock, so it cannot overlap anything
    $service = makeEventHours([
        makeEvent('2026-06-01', hours: 3.0),
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 10:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(4.0);
});

it('ignores an untimed event with a zero duration', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01'),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(0.0);
});

it('skips deleted and draft events', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 10:00']]),
        makeEvent('2026-06-01', [['2026-06-01 12:00', '2026-06-01 18:00']], deleted: true),
        makeEvent('2026-06-01', [['2026-06-01 19:00', '2026-06-01 23:00']], draft: true),
        makeEvent('2026-06-01', hours: 5.0, deleted: true),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(1.0);
});

it('ignores days outside the requested period', function () {
    $datasetPeriod = makePeriod('2026-06-01', '2026-06-03');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 17:00']]),
        makeEvent('2026-06-03', [['2026-06-03 09:00', '2026-06-03 17:00']]),
    ], $datasetPeriod);

    expect($service->forPeriod(makePeriod('2026-06-01', '2026-06-02'))->totalHours)->toBe(8.0);
});

it('returns zero when a period outside the dataset period is requested', function () {
    $datasetPeriod = makePeriod('2026-06-01', '2026-06-03');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 17:00']]),
    ], $datasetPeriod);

    expect($service->forPeriod(makePeriod('2026-06-01', '2026-06-05'))->totalHours)->toBe(0.0);
});

it('returns zero when there are no events at all', function () {
    $period = makePeriod('2026-06-01', '2026-06-03');

    expect(makeEventHours([], $period)->forPeriod($period)->totalHours)->toBe(0.0);
});

it('splits into slices that add up to the whole period', function () {
    $period = makePeriod('2026-06-01', '2026-06-30');

    $service = makeEventHours([
        makeEvent('2026-06-02', [['2026-06-02 09:00', '2026-06-02 17:00']]),
        makeEvent('2026-06-09', [['2026-06-09 09:00', '2026-06-09 13:00']]),
        makeEvent('2026-06-16', hours: 2.0),
        makeEvent('2026-06-23', [['2026-06-23 22:00', '2026-06-24 02:00']]),
    ], $period);

    $sliceTotal = $period->weeks()->sum(fn (PeriodData $week): float => $service->forPeriod($week)->totalHours);

    expect($sliceTotal)->toBe($service->forPeriod($period)->totalHours);
});

it('returns the same result however often it is asked', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $service = makeEventHours([
        makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 11:00']]),
        makeEvent('2026-06-01', [['2026-06-01 10:00', '2026-06-01 12:00']]),
    ], $period);

    expect($service->forPeriod($period)->totalHours)->toBe(3.0)
        ->and($service->forPeriod($period)->totalHours)->toBe(3.0)
        ->and($service->forPeriod($period)->totalHours)->toBe(3.0);
});

it('merges across the batches of a generator', function () {
    $period = makePeriod('2026-06-01', '2026-06-01');

    $batches = (function (): Generator {
        yield collect([makeEvent('2026-06-01', [['2026-06-01 09:00', '2026-06-01 11:00']])]);
        yield collect([makeEvent('2026-06-01', [['2026-06-01 10:00', '2026-06-01 12:00']])]);
    })();

    expect(EventHoursService::from($batches, $period)->forPeriod($period)->totalHours)->toBe(3.0);
});
