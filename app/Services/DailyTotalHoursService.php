<?php

namespace App\Services;

use App\Data\DailyDurationData;
use App\Data\PeriodData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class DailyTotalHoursService extends DayWalkingHoursService
{
    /** @var Collection<string, DailyDurationData> */
    private Collection $dailyDurations;

    public function __construct(DailyDurationData|Collection|array $dailyDurations, PeriodData $datasetPeriod)
    {
        parent::__construct($datasetPeriod);

        $this->dailyDurations = Collection::wrap($dailyDurations)
            ->keyBy(fn (DailyDurationData $dailyDuration): string => $dailyDuration->day->format('Y-m-d'));
    }

    /**
     * @param  DailyDurationData|Collection<string,DailyDurationData>  $dailyDurations
     */
    public static function from(DailyDurationData|Collection $dailyDurations, PeriodData $datasetPeriod): self
    {
        return new self($dailyDurations, $datasetPeriod);
    }

    protected function getSecondsOfDay(CarbonImmutable $day): int
    {
        $applicableDailyDuration = $this->dailyDurations->get($day->format('Y-m-d'));

        return $applicableDailyDuration?->duration?->totalSeconds ?: 0;
    }
}
