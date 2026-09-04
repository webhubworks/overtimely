<?php

namespace App\Services;

use App\Data\EventData;
use App\Data\TimestampData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class EventHoursService extends HoursService
{
    /** @var Collection<string, Collection<int, TimestampData>> */
    private Collection $timestampsByDay;

    /**
     * @param  EventData|Collection<int,EventData>|array<int,EventData>  $events
     */
    public function __construct(EventData|Collection|array $events)
    {
        $eventsGroupedByDay = Collection::wrap($events)
            ->groupBy(fn (EventData $event): string => $event->day->format('Y-m-d'));

        /**
         * Extract the timestamp collections of each event into its day's group.
         */
        $this->timestampsByDay = $eventsGroupedByDay
            ->map(function (Collection $eventsOfDay): Collection {
                return $eventsOfDay
                    ->map(fn (EventData $event): Collection => $event->timestamps)
                    ->flatten();
            });
    }

    /**
     * @param  EventData|Collection<int,EventData>  $events
     */
    public static function fromEvents(EventData|Collection $events): self
    {
        return new self($events);
    }

    protected function getSecondsOfDay(CarbonImmutable $day): int
    {
        $timestampsOfDay = $this->timestampsByDay->get($day->format('Y-m-d'));

        if (! $timestampsOfDay || $timestampsOfDay->isEmpty()) {
            return 0;
        }

        $sequentialTimestamps = $this->resolveOverlappingTimestamps($timestampsOfDay);

        return $sequentialTimestamps->sum(fn (TimestampData $timestamp) => $timestamp->seconds());
    }

    /**
     * Walks timestamps to check for overlaps and merge overlapping timestamps into one, creating a new collection of sequential (non-overlapping) timestamps.
     *
     * Each top-level iteration takes a timestamp and checks if it overlaps with any other timestamp.
     * If it does, it merges the two timestamps into one and removes the overlapping timestamp from the collection before
     * restarting the overlap-checking process, now comparing against the just merged timestamp.
     * This process continues until there are no more overlapping timestamps left.
     *
     * The (merged) timestamp is added to the output collection as it now cannot have any overlaps with any other timestamp.
     * A new top-level iteration starts with the next available timestamp still in the input collection.
     *
     * @param  Collection<int, TimestampData>  $timestamps
     * @return Collection<int, TimestampData>
     */
    private function resolveOverlappingTimestamps(Collection $timestamps): Collection
    {
        $sequentialTimestamps = collect();

        while ($timestamps->isNotEmpty()) {
            /** @var TimestampData $timestamp */
            $timestamp = $timestamps->shift();

            do {
                $merged = false;

                foreach ($timestamps as $key => $otherTimestamp) {
                    if ($timestamp->overlapsWith($otherTimestamp)) {
                        $timestamp = $timestamp->mergeWith($otherTimestamp);
                        $timestamps->forget($key);
                        $merged = true;
                        break;
                    }
                }

            } while ($merged);

            $sequentialTimestamps->push($timestamp);
        }

        return $sequentialTimestamps;
    }
}
