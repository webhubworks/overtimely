<?php

namespace App\Services;

use App\Data\DurationData;
use App\Data\PeriodData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;

abstract readonly class HoursService
{
    /**
     * Returns the logged hours for a given period.
     */
    public function forPeriod(PeriodData $period): DurationData
    {
        $totalSeconds = 0;

        foreach (CarbonPeriodImmutable::create($period->since, $period->until) as $day) {
            $totalSeconds += $this->getSecondsOfDay($day);
        }

        return DurationData::fromTotalSeconds($totalSeconds);
    }

    /**
     * Returns the logged hours for a given day in seconds.
     */
    abstract protected function getSecondsOfDay(CarbonImmutable $day): int;
}
