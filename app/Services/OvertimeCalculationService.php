<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\HoursData;
use App\DataTransferObjects\OvertimeData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

final readonly class OvertimeCalculationService
{
    /** @param Collection<int, CapacityData> $capacities */
    public function __construct(
        private Collection $capacities,
    ) {}

    /**
     * Cumulative overtime balance over [since, until]: logged hours minus the
     * expected working hours derived from the user's capacities.
     *
     * @param  CarbonImmutable  $until  Last completed day (typically yesterday)
     */
    public function forPeriod(
        HoursData $logged,
        CarbonImmutable $since,
        CarbonImmutable $until,
    ): OvertimeData {
        return OvertimeData::fromHours(
            logged: $logged,
            expected: $this->calculateTotalCapacityForPeriod($since, $until),
        );
    }

    private function calculateTotalCapacityForPeriod(CarbonImmutable $since, CarbonImmutable $until): HoursData
    {
        $totalCapacity = 0.0;

        foreach (CarbonPeriod::create($since, $until) as $day) {
            $day = CarbonImmutable::instance($day);
            $capacity = $this->determineCapacityForDay($day);

            if ($capacity !== null && $this->isWorkDayOfCapacity($capacity, $day)) {
                $totalCapacity += $this->getOrCalculateDailyCapacity($capacity);
            }
        }

        return HoursData::fromHours($totalCapacity);
    }

    /**
     * The applicable capacity for a given day: the covering window with the
     * latest start date, so a specific dated capacity wins over the open-ended
     * default (start 1970-01-01).
     */
    private function determineCapacityForDay(CarbonImmutable $day): ?CapacityData
    {
        return $this->capacities
            ->sortByDesc(fn (CapacityData $capacity): int => $capacity->startDate->getTimestamp())
            ->first(fn (CapacityData $capacity): bool => $day->greaterThanOrEqualTo($capacity->startDate));
    }

    private function isWorkDayOfCapacity(CapacityData $capacity, CarbonImmutable $day): bool
    {
        $workDaysOfCapacity = collect(explode(',', $capacity->workDays));

        return $workDaysOfCapacity->contains(strtoupper($day->format('D')));
    }

    private function getOrCalculateDailyCapacity(CapacityData $capacity): float
    {
        if ($capacity->dailyCapacity > 0) {
            return $capacity->dailyCapacity;
        }

        return $capacity->weeklyWorkingDays > 0
            ? $capacity->weeklyCapacity / $capacity->weeklyWorkingDays
            : 0.0;
    }
}
