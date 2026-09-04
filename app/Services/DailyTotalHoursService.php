<?php

namespace App\Services;

use App\Data\DailyDurationData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class DailyTotalHoursService extends HoursService
{
    /** @var Collection<string, DailyDurationData> */
    private Collection $dailyDurations;

    public function __construct(DailyDurationData|Collection|array $dailyDurations)
    {
        $this->dailyDurations = Collection::wrap($dailyDurations)
            ->keyBy(fn (DailyDurationData $dailyDuration): string => $dailyDuration->day->format('Y-m-d'));
    }

    /**
     * @param  DailyDurationData|Collection<string,DailyDurationData>  $dailyDurations
     */
    public static function fromDailyDurations(DailyDurationData|Collection $dailyDurations): self
    {
        return new self($dailyDurations);
    }

    protected function getSecondsOfDay(CarbonImmutable $day): int
    {
        $applicableDailyDuration = $this->dailyDurations->get($day->format('Y-m-d'));

        return $applicableDailyDuration?->duration?->totalSeconds ?: 0;
    }
}
