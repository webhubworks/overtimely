<?php

namespace App\Services;

use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\DurationData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;

final readonly class OvertimeBalanceCalculationService
{
    private Collection $capacities;

    /** @param Collection<int, CapacityData> $capacities */
    public function __construct(Collection $capacities)
    {
        $this->capacities = $capacities
            ->sortByDesc(fn (CapacityData $capacity): int => $capacity->startDate->getTimestamp())
            ->values();
    }

    public static function fromCapacities(Collection $capacities): self
    {
        return new self($capacities);
    }

    /**
     * Cumulative overtime balance over [since, until]: logged hours minus the
     * expected working hours derived from the user's capacities.
     *
     * @param  CarbonImmutable  $until  Last completed day (typically yesterday)
     */
    public function forPeriod(
        CarbonImmutable $since,
        CarbonImmutable $until,
        DurationData $logged,
    ): BalanceData {
        return BalanceData::fromHours(
            logged: $logged,
            expected: $this->calculateTotalCapacityForPeriod($since, $until),
        );
    }

    private function calculateTotalCapacityForPeriod(CarbonImmutable $since, CarbonImmutable $until): DurationData
    {
        $totalCapacity = 0.0;

        foreach (CarbonPeriodImmutable::create($since, $until) as $day) {
            $totalCapacity += $this->getCapacityForDay($day);
        }

        return DurationData::fromTotalHours($totalCapacity);
    }

    private function getCapacityForDay(CarbonImmutable $day): float
    {
        $capacityForDay = $this->determineCapacityForDay($day);

        $isWorkDayOfThisCapacity = $this->isWorkDayOfCapacity($capacityForDay, $day);

        return $isWorkDayOfThisCapacity
            ? $capacityForDay->dailyCapacity
            : 0.0;
    }

    /**
     * The applicable capacity for a given day:
     * Capacities are sorted by start date so the "latest" capacity wins.
     */
    private function determineCapacityForDay(CarbonImmutable $day): ?CapacityData
    {
        return $this->capacities
            ->first(fn (CapacityData $capacity): bool => $day->greaterThanOrEqualTo($capacity->startDate));
    }

    private function isWorkDayOfCapacity(CapacityData $capacity, CarbonImmutable $day): bool
    {
        return $capacity->workDays
            ->contains(strtoupper($day->format('D')));
    }
}
