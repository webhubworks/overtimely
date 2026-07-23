<?php

namespace App\Services;

use App\DataTransferObjects\DailyDurationData;
use App\DataTransferObjects\DurationData;
use App\DataTransferObjects\PeriodData;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;

final readonly class LoggedHoursService
{
    /**
     * @var Collection<int, DailyDurationData>
     */
    private Collection $dailyDurations;

    public function __construct(DailyDurationData|Collection $dailyDurations)
    {
        $this->dailyDurations = Collection::wrap($dailyDurations)
            ->sortBy(fn (DailyDurationData $duration): int => $duration->day->getTimestamp())
            ->values();
    }

    public static function fromDailyDurations(DailyDurationData|Collection $dailyDurations): self
    {
        return new self($dailyDurations);
    }

    public function forPeriod(PeriodData $period): DurationData
    {
        $totalSeconds = 0;

        foreach (CarbonPeriodImmutable::create($period->since, $period->until) as $day) {
            $totalSeconds += $this->getDurationOfDay($day);
        }

        return DurationData::fromTotalSeconds($totalSeconds);
    }

    /**
     * Returns the duration for a given day in seconds.
     */
    private function getDurationOfDay(CarbonImmutable $day): int
    {
        $applicableDailyDuration = $this->dailyDurations
            ->first(fn (DailyDurationData $dailyDuration) => $dailyDuration->day->equalTo($day));

        return $applicableDailyDuration?->duration?->totalSeconds ?: 0;
    }
}
