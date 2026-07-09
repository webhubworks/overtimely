<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\ExpectedHoursData;
use App\DataTransferObjects\LoggedHoursData;
use App\DataTransferObjects\OvertimeData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

final class OvertimeCalculator
{
    /**
     * Cumulative overtime balance over [since, until]: logged hours minus the
     * expected working hours derived from the user's capacities.
     *
     * @param  Collection<int, CapacityData>  $capacities
     * @param  CarbonImmutable  $until  Last completed day (typically yesterday)
     */
    public function forPeriod(
        LoggedHoursData $logged,
        Collection $capacities,
        CarbonImmutable $since,
        CarbonImmutable $until,
    ): OvertimeData {
        return OvertimeData::fromCalculationResults(
            logged: $logged,
            expected: $this->expectedHours($capacities, $since, $until),
        );
    }

    /** @param Collection<int, CapacityData> $capacities */
    private function expectedHours(Collection $capacities, CarbonImmutable $since, CarbonImmutable $until): ExpectedHoursData
    {
        $expected = 0.0;

        foreach (CarbonPeriod::create($since, $until) as $day) {
            $capacity = $this->capacityFor($capacities, CarbonImmutable::instance($day));

            if ($capacity !== null && $this->isWorkingDay($capacity, $day)) {
                $expected += $this->dailyCapacity($capacity);
            }
        }

        return ExpectedHoursData::fromHours($expected);
    }

    /**
     * The applicable capacity for a given day: the covering window with the
     * latest start date, so a specific dated capacity wins over the open-ended
     * default (start 1970-01-01).
     *
     * @param  Collection<int, CapacityData>  $capacities
     */
    private function capacityFor(Collection $capacities, CarbonImmutable $day): ?CapacityData
    {
        return $capacities
            ->filter(fn (CapacityData $c): bool => $day->greaterThanOrEqualTo($c->startDate)
                && ($c->endDate === null || $day->lessThanOrEqualTo($c->endDate)))
            ->sortByDesc(fn (CapacityData $c): int => $c->startDate->getTimestamp())
            ->first();
    }

    private function isWorkingDay(CapacityData $capacity, CarbonImmutable $day): bool
    {
        // work_days is "MON,TUE,WED,THU,FRI"; format('D') yields "Mon","Tue",...
        return in_array(strtoupper($day->format('D')), explode(',', $capacity->workDays), true);
    }

    private function dailyCapacity(CapacityData $capacity): float
    {
        if ($capacity->dailyCapacity > 0) {
            return $capacity->dailyCapacity;
        }

        return $capacity->weeklyWorkingDays > 0
            ? $capacity->weeklyCapacity / $capacity->weeklyWorkingDays
            : 0.0;
    }
}
