<?php

namespace App\Services;

use App\Data\EventData;
use App\Data\PeriodData;
use App\Data\TimestampData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EventHoursService extends DayWalkingHoursService
{
    private const string DAY_KEY_FORMAT = 'Y-m-d';

    /** @var Collection<string, int> */
    private Collection $secondsByDay;

    /**
     * @param  Collection<int, EventData>  $events
     */
    public function __construct(Collection $events, PeriodData $datasetPeriod)
    {
        parent::__construct($datasetPeriod);

        [$untimedEvents, $timedEvents] = $events
            ->reject(fn (EventData $event): bool => $event->draft || $event->deleted)
            ->partition(fn (EventData $event): bool => $event->timestamps->isEmpty());

        $this->secondsByDay = $this->timedSecondsByDay($timedEvents);

        foreach ($this->untimedSecondsByDay($untimedEvents) as $day => $seconds) {
            $this->secondsByDay[$day] = ($this->secondsByDay[$day] ?? 0) + $seconds;
        }
    }

    /**
     * @param  Collection<int, EventData>  $events
     */
    public static function from(Collection $events, PeriodData $datasetPeriod): self
    {
        return new self($events, $datasetPeriod);
    }

    protected function getSecondsOfDay(CarbonImmutable $day): int
    {
        return $this->secondsByDay->get($day->format(self::DAY_KEY_FORMAT)) ?? 0;
    }

    /**
     * Walks timestamps to check for overlaps and merge overlapping timestamps into one, creating a new collection of sequential (non-overlapping) timestamps.
     *
     * @param  Collection<int, TimestampData>  $timestamps
     * @return Collection<int, TimestampData>
     */
    private function mergeOverlappingTimestamps(Collection $timestamps): Collection
    {
        if ($timestamps->isEmpty()) {
            return collect();
        }

        $inputTimestamps = $timestamps
            ->sortBy(fn (TimestampData $timestamp): int => $timestamp->from->unix())
            ->values();

        $sequentialTimestamps = collect();
        $openTimestamp = $inputTimestamps->shift();

        foreach ($inputTimestamps as $nextTimestamp) {
            if ($openTimestamp->overlapsWith($nextTimestamp)) {
                $openTimestamp = $openTimestamp->mergeWith($nextTimestamp);
            } else {
                $sequentialTimestamps->push($openTimestamp);
                $openTimestamp = $nextTimestamp;
            }
        }

        $sequentialTimestamps->push($openTimestamp);

        return $sequentialTimestamps;
    }

    /**
     * @param  Collection<int, EventData>  $events
     * @return Collection<string, int>
     */
    private function timedSecondsByDay(Collection $events): Collection
    {
        return $events
            ->flatMap(function (EventData $event): Collection {
                return $event->timestamps
                    ->flatMap(fn (TimestampData $timestamp): Collection => $timestamp->fragments());
            })
            ->groupBy(fn (TimestampData $timestamp): string => $timestamp->from->format(self::DAY_KEY_FORMAT))
            ->map(fn (Collection $timestampsOfDay): Collection => $this->mergeOverlappingTimestamps($timestampsOfDay))
            ->map(function (Collection $timestampsOfDay): int {
                return $timestampsOfDay
                    ->sum(fn (TimestampData $timestamp): int => $timestamp->seconds());
            });
    }

    /**
     * @param  Collection<int, EventData>  $events
     * @return Collection<string, int>
     */
    private function untimedSecondsByDay(Collection $events): Collection
    {
        return $events
            ->reject(fn (EventData $event): bool => $event->duration->isZero())
            ->groupBy(fn (EventData $event): string => $event->day->format(self::DAY_KEY_FORMAT))
            ->map(function (Collection $eventsOfDay): int {
                return $eventsOfDay
                    ->sum(fn (EventData $event): int => $event->duration->totalSeconds);
            });
    }
}
