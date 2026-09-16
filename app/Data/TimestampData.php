<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class TimestampData extends Data
{
    /** If `$form` is after `$to`, they are swapped. */
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class)]
        public CarbonImmutable $from,
        #[WithCast(DateTimeInterfaceCast::class)]
        public CarbonImmutable $to,
    ) {
        if ($from->greaterThan($to)) {
            $this->from = $to;
            $this->to = $from;
        }
    }

    /**
     * This implements an established logic by inverting the logic for when two timestamps do **not** overlap, since it is easier to grasp.
     *
     * The logic is as follows:\
     * Assuming the start of A is before the end of A and the start of B is before the end of B, then ...
     * 1. If the start of A is after the end of B, A is entirely after B. No overlap.
     * 2. If the end of A is before the start of B, A is entirely before B. No overlap.
     *
     * Terse notation of the above:\
     * If `A.from > B.to || A.to < B.from`, then A does **not** overlap with B.\
     * Inverting this to get the logic we want (**does** A overlap with B?)
     * 1. `A.from > B.to || A.to < B.from` inverted is ...
     * 2. `! (A.from > B.to || A.to < B.from)` is equivalent to ...
     * 3. `!(A.from > B.to) && !(A.to < B.from)` is equivalent to ...
     * 4. `A.from < B.to && A.to > B.from`
     *
     * Actually, the correct equivalent of 3 would use <= and >= for comparisons,
     * but we explicitly allow the exact overlapping of timestamps at
     * their start or end points since Timely does so as well.
     *
     * An equivalent logic / alternative implementation would also be:\
     * `maximum(A.from, B.from) < minimum(A.to, B.to)`
     */
    public function overlapsWith(self $otherTimestamp): bool
    {
        /**
         * Equivalent Logic / Alternative Implementation:
         * ```
         * $maxFrom = $this->from->max($otherTimestamp->from)
         * $minTo = $this->to->min($otherTimestamp->to)
         * return $maxFrom->lessThan($minTo);
         * ```
         */
        return $this->from->lessThan($otherTimestamp->to)
            && $this->to->greaterThan($otherTimestamp->from);
    }

    /**
     * Returns a new timestamp starting at the earliest point and ending at the latest point of both timestamps.
     */
    public function mergeWith(self $otherTimestamp): self
    {
        return new self(
            $this->from->min($otherTimestamp->from),
            $this->to->max($otherTimestamp->to),
        );
    }

    public function seconds(): int
    {
        return (int) round($this->to->diffInSeconds($this->from, absolute: true));
    }

    public function duration(): DurationData
    {
        return DurationData::fromTotalSeconds($this->seconds());
    }

    public function earliestMidnightInTimespan(): CarbonImmutable
    {
        return $this->from->addDay()->startOfDay();
    }

    public function crossesMidnight(): bool
    {
        return $this->to->greaterThan($this->earliestMidnightInTimespan());
    }

    public function isSingleDay(): bool
    {
        return ! $this->crossesMidnight();
    }

    /**
     * Returns an array containing:
     * - A `TimestampData` spanning `from` to the earliest midnight in the timespan.
     * - A `TimestampData` spanning from the earliest midnight in the timespan to `to`.
     *
     * @return self[]
     */
    public function splitOnEarliestMidnight(): array
    {
        $nextMidnight = $this->earliestMidnightInTimespan();

        return [
            new self($this->from, $nextMidnight),
            new self($nextMidnight, $this->to),
        ];
    }

    /**
     * Splits this timestamp into single-day fragments if it spans multiple days.
     *
     * @return Collection<int,self>
     */
    public function fragments(): Collection
    {
        if ($this->isSingleDay()) {
            return collect([$this]);
        }

        $fragments = collect();
        $fragment = $this;

        do {
            [$fragments[], $fragment] = $fragment->splitOnEarliestMidnight();
        } while ($fragment->crossesMidnight());

        $fragments->push($fragment);

        return $fragments;
    }
}
