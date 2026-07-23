<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\DurationData;
use App\DataTransferObjects\PeriodData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;

final readonly class CapacityService
{
    /**
     * @var Collection<int, CapacityData>
     */
    private Collection $capacities;

    public function __construct(CapacityData|Collection $capacities)
    {
        $this->capacities = Collection::wrap($capacities)
            ->sortByDesc(fn (CapacityData $capacity): int => $capacity->startDate->getTimestamp())
            ->values();
    }

    public static function fromCapacities(CapacityData|Collection $capacities): self
    {
        return new self($capacities);
    }

    public function forPeriod(PeriodData $period): DurationData
    {
        $totalCapacity = 0.0;

        foreach (CarbonPeriodImmutable::create($period->since->startOfDay(), $period->until->startOfDay()) as $day) {
            $totalCapacity += $this->getCapacityOfDay($day);
        }

        return DurationData::fromTotalHours($totalCapacity);
    }

    /**
     * Returns the capacity for a given day in hours.
     * Capacities are sorted by start date in descending order so the "latest" capacity wins.
     */
    private function getCapacityOfDay(CarbonImmutable $day): float
    {
        $applicableCapacity = $this->capacities
            ->first(fn (CapacityData $capacity): bool => $capacity->hasDay($day));

        return $applicableCapacity->dailyCapacity ?: 0.0;
    }
}
