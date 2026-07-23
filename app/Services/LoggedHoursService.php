<?php

namespace App\Services;

use App\DataTransferObjects\DurationData;
use App\DataTransferObjects\PeriodData;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;

final readonly class LoggedHoursService
{
    /**
     * @param  Collection<string, DurationData>  $dailyDurations
     */
    public function __construct(private Collection $dailyDurations) {}

    /**
     * @param  Collection<string, DurationData>  $dailyDurations
     */
    public static function fromDailyDurations(Collection $dailyDurations): self
    {
        return new self($dailyDurations);
    }

    public function forPeriod(PeriodData $period): DurationData
    {
        $totalSeconds = 0;

        foreach (CarbonPeriodImmutable::create($period->since, $period->until) as $day) {
            $totalSeconds += $this->dailyDurations->get($day->format('Y-m-d'))?->totalSeconds ?? 0;
        }

        return DurationData::fromTotalSeconds($totalSeconds);
    }
}
