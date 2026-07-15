<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class PeriodData extends Data
{
    public function __construct(
        public CarbonImmutable $since,
        public CarbonImmutable $until,
    ) {}

    /**
     * Split [since, until] into one entry per calendar month. The first and
     * last entries are clamped to the range, so partial months are kept as the
     * slice that falls inside the range.
     *
     * Dates are treated as calendar-only (midnight): month ends are normalized
     * with startOfDay() to stay consistent with the rest of the app, and the
     * cursor advances from the first of the month to avoid month-overflow.
     *
     * @return Collection<int, self>
     */
    public static function monthlySplit(CarbonImmutable $since, CarbonImmutable $until): Collection
    {
        $periods = new Collection;

        for ($cursor = $since; $cursor->lessThanOrEqualTo($until); $cursor = $cursor->startOfMonth()->addMonth()) {
            $monthEnd = $cursor->endOfMonth()->startOfDay();

            $periods->push(new self(
                since: $cursor,
                until: $monthEnd->min($until),
            ));
        }

        return $periods;
    }
}
