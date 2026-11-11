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

    /** @var Collection<int, TimestampData> */
    private Collection $allTimestamps;

    /** @var Collection<string, int> */
    private Collection $secondsByDay;

    /** @var Collection<string, int> */
    private Collection $untimedSecondsByDay;

    /**
     * @param  iterable<int, EventData>  $events
     */
    public function __construct(iterable $events, PeriodData $datasetPeriod)
    {
        parent::__construct($datasetPeriod);

        $this->allTimestamps = collect();
        $this->untimedSecondsByDay = collect();

        /** @var EventData $event */
        foreach ($events as $event) {
            if ($event->draft || $event->deleted) {
                continue;
            }

            if ($event->timestamps->isEmpty()) {
                $this->handleUntimedEvent($event);

                continue;
            }

            $this->handleTimedEvent($event);
        }

        $timestampsByDay = $this->allTimestamps
            ->groupBy(function (TimestampData $timestamp): string {
                return $timestamp->from->format(self::DAY_KEY_FORMAT);
            });

        $mergedTimestampsByDay = $timestampsByDay
            ->map(function (Collection $timestampsOfDay): Collection {
                return $this->mergeOverlappingTimestamps($timestampsOfDay);
            });

        $this->secondsByDay = $mergedTimestampsByDay
            ->map(function (Collection $timestampsOfDay): int {
                return $timestampsOfDay->sum(fn (TimestampData $timestamp): int => $timestamp->seconds());
            });

        foreach ($this->untimedSecondsByDay as $day => $untimedSeconds) {
            $this->secondsByDay[$day] = ($this->secondsByDay[$day] ?? 0) + $untimedSeconds;
        }
    }

    /**
     * @param  iterable<int, EventData>  $events
     */
    public static function from(iterable $events, PeriodData $datasetPeriod): self
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
     * Handles an event without timestamps, using its own duration and date instead of the underlying timestamps.
     */
    private function handleUntimedEvent(EventData $event): void
    {
        if ($event->duration->isZero()) {
            return;
        }

        $key = $event->day->format(self::DAY_KEY_FORMAT);

        $this->untimedSecondsByDay[$key] = ($this->untimedSecondsByDay[$key] ?? 0) + $event->duration->totalSeconds;
    }

    /**
     * Handles an event with timestamps, splitting multi-day timestamps into their fragments.
     */
    private function handleTimedEvent(EventData $event): void
    {
        $timestampFragments = $event->timestamps
            ->flatMap(fn (TimestampData $timestamp): Collection => $timestamp->fragments());

        $this->allTimestamps->push(...$timestampFragments);
    }
}
