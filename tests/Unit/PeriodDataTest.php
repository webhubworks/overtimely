<?php

use App\Data\PeriodData;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, PeriodData>  $slices
 * @return array<int, string>
 */
function coveredDays(Collection $slices): array
{
    $days = [];

    foreach ($slices as $slice) {
        for ($day = $slice->since; $day->lessThanOrEqualTo($slice->until); $day = $day->addDay()) {
            $days[] = $day->format('Y-m-d');
        }
    }

    return $days;
}

it('covers every day of the period exactly once when split into months', function (string $since, string $until) {
    $period = makePeriod($since, $until);

    $days = coveredDays($period->months());
    $dayCount = (int) $period->since->diffInDays($period->until) + 1;

    expect($days)->toHaveCount($dayCount)
        ->and(array_unique($days))->toHaveCount($dayCount)
        ->and($days[0])->toBe($period->since->format('Y-m-d'))
        ->and(end($days))->toBe($period->until->format('Y-m-d'));
})->with([
    'full calendar months' => ['2026-01-01', '2026-06-15'],
    'starting on a 31st' => ['2026-01-31', '2026-06-15'],
    'ending mid month' => ['2026-03-30', '2026-06-15'],
    'within a single month' => ['2026-06-10', '2026-06-20'],
    'a single day' => ['2026-06-15', '2026-06-15'],
    'across a year boundary' => ['2025-11-15', '2026-02-10'],
    'across a leap February' => ['2028-01-31', '2028-03-31'],
]);

it('keeps a month the start date has no counterpart in', function () {
    $months = makePeriod('2026-01-31', '2026-06-15')->months();

    expect($months->map(fn (PeriodData $month): string => $month->since->format('Y-m'))->all())
        ->toBe(['2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06']);
});

it('clamps the first and last month to the period boundaries', function () {
    $months = makePeriod('2026-01-31', '2026-06-15')->months();

    expect((string) $months->first())->toBe('2026-01-31 to 2026-01-31')
        ->and((string) $months->last())->toBe('2026-06-01 to 2026-06-15');
});

it('covers every day of the period exactly once when split into weeks', function (string $since, string $until) {
    $period = makePeriod($since, $until);

    $days = coveredDays($period->weeks());
    $dayCount = (int) $period->since->diffInDays($period->until) + 1;

    expect($days)->toHaveCount($dayCount)
        ->and(array_unique($days))->toHaveCount($dayCount)
        ->and($days[0])->toBe($period->since->format('Y-m-d'))
        ->and(end($days))->toBe($period->until->format('Y-m-d'));
})->with([
    'full calendar weeks' => ['2026-06-01', '2026-07-12'],
    'starting on a Sunday' => ['2026-06-07', '2026-07-15'],
    'ending mid week' => ['2026-06-01', '2026-07-15'],
    'within a single week' => ['2026-06-03', '2026-06-04'],
    'a single day' => ['2026-06-15', '2026-06-15'],
    'across a year boundary' => ['2026-12-28', '2027-01-10'],
]);

it('keeps the final partial week', function () {
    $weeks = makePeriod('2026-06-07', '2026-07-15')->weeks();

    expect((string) $weeks->last())->toBe('2026-07-13 to 2026-07-15');
});

it('clamps the first and last week to the period boundaries', function () {
    $weeks = makePeriod('2026-06-07', '2026-07-15')->weeks();

    expect((string) $weeks->first())->toBe('2026-06-07 to 2026-06-07')
        ->and((string) $weeks->last())->toBe('2026-07-13 to 2026-07-15');
});
