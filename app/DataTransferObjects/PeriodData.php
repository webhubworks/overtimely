<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class PeriodData extends Data
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /**
     * Split [since, until] into one entry per calendar month. The first and
     * last entries are clamped to the range, so partial months are kept as the
     * slice that falls inside the range.
     *
     * A monthly period anchored on the first of the start month yields each
     * month's opening day; anchoring there keeps the "+1 month" step off
     * day-of-month overflow. Each boundary is then clamped into the range with
     * max()/min(), and month ends are normalized to midnight to stay
     * calendar-only like the rest of the app.
     *
     * @return Collection<int, self>
     */
    public static function months(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return collect(CarbonPeriodImmutable::create($from->startOfMonth(), '1 month', $to))
            ->map(fn (CarbonImmutable $monthStart): self => new self(
                start: $monthStart->startOfDay()->max($from),
                end: $monthStart->endOfMonth()->startOfDay()->min($to),
            ));
    }
}
