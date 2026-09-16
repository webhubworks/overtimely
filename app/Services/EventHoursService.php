<?php

namespace App\Services;

use App\Data\EventData;
use App\Data\PeriodData;
use App\Data\TimestampData;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Support\Collection;

final class EventHoursService extends DayWalkingHoursService
{
    private const string DAY_KEY_FORMAT = 'Y-m-d';

    /** @var Collection<int, TimestampData> */
    private Collection $allTimestamps;

    /** @var Collection<string, int> */
    private Collection $secondsByDay;

    private Collection $secondsOfEventDurationsByDay;

    /**
     * @param  EventData|Generator<int,Collection<int,EventData>>|iterable<int,iterable<int,EventData>>  $eventOrBatches
     */
    public function __construct(EventData|Generator|iterable $eventOrBatches, PeriodData $datasetPeriod)
    {
        parent::__construct($datasetPeriod);

        $this->allTimestamps = collect();
        $this->secondsByDay = collect();
        $this->secondsOfEventDurationsByDay = collect();

        $batches = $eventOrBatches instanceof EventData
            ? [[$eventOrBatches]] // wrap single EventData in a batch
            : $eventOrBatches;

        foreach ($batches as $events) {

            /** @var EventData $event */
            foreach ($events as $event) {
                if ($event->draft || $event->deleted) {
                    continue;
                }

                if ($event->timestamps->isEmpty()) {
                    $this->handleEventByDuration($event);

                    continue;
                }

                $this->handleEventByTimestamps($event);
            }
        }

        $timestampsByDay = $this->allTimestamps
            ->groupBy(function (TimestampData $timestamp) {
                return $timestamp->from->format(self::DAY_KEY_FORMAT);
            });

        $mergedTimestampsByDay = $timestampsByDay
            ->map(function (Collection $timestampsOfDay) {
                return $this->mergeOverlappingTimestamps($timestampsOfDay);
            });

        $this->secondsByDay = $mergedTimestampsByDay
            ->map(function (Collection $timestampsOfDay) {
                return $timestampsOfDay->sum(fn (TimestampData $timestamp) => $timestamp->seconds());
            });

        foreach ($this->secondsOfEventDurationsByDay as $day => $secondsOfEventDurations) {
            $this->secondsByDay[$day] = ($this->secondsByDay[$day] ?? 0) + $secondsOfEventDurations;
        }
    }

    /**
     * @param  EventData|Generator<int,Collection<int,EventData>>|iterable  $eventOrBatches
     */
    public static function from(EventData|Generator|iterable $eventOrBatches, PeriodData $datasetPeriod): self
    {
        return new self($eventOrBatches, $datasetPeriod);
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
        $inputTimestamps = $timestamps
            ->sortBy(fn (TimestampData $timestamp) => $timestamp->from->unix())
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

    private function handleEventByDuration(EventData $event): void
    {
        if ($event->duration->isZero()) {
            return;
        }

        $key = $event->day->format(self::DAY_KEY_FORMAT);

        $this->secondsOfEventDurationsByDay[$key] = ($this->secondsOfEventDurationsByDay[$key] ?? 0) + $event->duration->totalSeconds;
    }

    private function handleEventByTimestamps(EventData $event): void
    {
        $timestampFragments = $event->timestamps
            ->flatMap(fn (TimestampData $timestamp) => $timestamp->fragments());

        $this->allTimestamps->push(...$timestampFragments);
    }
}
