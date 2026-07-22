<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class PeriodData extends Data
{
    public function __construct(
        public CarbonImmutable $since,
        public CarbonImmutable $until,
    ) {}

    /**
     * Returns a period for the interval [`since`, `until`].
     */
    public static function fromBoundaries(CarbonImmutable $since, CarbonImmutable $until): self
    {
        return new self($since->startOfDay(), $until->startOfDay());
    }

    public function __toString(): string
    {
        return "{$this->since->format('Y-m-d')} to {$this->until->format('Y-m-d')}";
    }

    /**
     * Splits this period into one period per calendar month.
     * The first and last periods are clamped to this period's boundaries,
     * so partial months are kept as the slice that falls inside the boundaries.
     *
     * @return Collection<int, self>
     */
    public function months(): Collection
    {
        return collect(CarbonPeriodImmutable::create($this->since, '1 month', $this->until))
            ->map(fn (CarbonImmutable $monthStart): self => new self(
                since: $monthStart->startOfMonth()->max($this->since),
                until: $monthStart->endOfMonth()->startOfDay()->min($this->until),
            ));
    }

    /**
     * Splits this period into one period per calendar week.
     * The first and last periods are clamped to this period's boundaries,
     * so partial weeks are kept as the slice that falls inside the boundaries.
     *
     * @return Collection<int, self>
     */
    public function weeks(): Collection
    {
        return collect(CarbonPeriodImmutable::create($this->since, '1 week', $this->until))
            ->map(fn (CarbonImmutable $weekStart): self => new self(
                since: $weekStart->startOfWeek()->max($this->since),
                until: $weekStart->endOfWeek()->startOfDay()->min($this->until),
            ));
    }
}
