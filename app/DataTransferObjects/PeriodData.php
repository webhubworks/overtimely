<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class PeriodData extends Data
{
    public function __construct(
        public ?CarbonImmutable $since,
        public ?CarbonImmutable $until,
    ) {}

    public static function fromDates(?CarbonImmutable $since, ?CarbonImmutable $until): self
    {
        return new self($since, $until);
    }

    public function __toString(): string
    {
        return "{$this->since->format('jS \o\f F Y')} to {$this->until->format('jS \o\f F Y')}";
    }

    /**
     * Split [`from`, `to`] into one entry per calendar month.
     * The first and last entries are clamped to the range,
     * so partial months are kept as the slice that falls inside the range.
     *
     * @return Collection<int, self>
     */
    public static function months(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return collect(CarbonPeriodImmutable::create($from->startOfMonth(), '1 month', $to))
            ->map(fn (CarbonImmutable $monthStart): self => new self(
                since: $monthStart->startOfDay()->max($from),
                until: $monthStart->endOfMonth()->startOfDay()->min($to),
            ));
    }
}
