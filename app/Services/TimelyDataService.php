<?php

namespace App\Services;

use App\Data\CapacityData;
use App\Data\CurrentUserData;
use App\Data\DailyDurationData;
use App\Data\DurationData;
use App\Data\EventData;
use App\Data\PeriodData;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyDataService
{
    /**
     * Timely's API has a maximum of 5000 events per page.
     * We leverage that to reduce the number of requests to a minimum.
     */
    private const int EVENTS_PER_PAGE = 5000;

    public function __construct(
        private PendingRequest $client,
        private int $accountId,
        private ?int $userId = null,
        private ?CarbonImmutable $createdAt = null,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function getCurrentUser(): CurrentUserData
    {
        return CurrentUserData::from(
            $this->client
                ->get("{$this->accountId}/users/current")
                ->json()
        );
    }

    /**
     * @throws ConnectionException
     */
    public function getCreationDate(): CarbonImmutable
    {
        return $this->createdAt ?? $this->getCurrentUser()->createdAt->startOfDay();
    }

    /** @return Collection<int, CapacityData>
     * @throws ConnectionException
     */
    public function getCapacities(): Collection
    {
        return CapacityData::collect(
            $this->client
                ->get("{$this->accountId}/users/{$this->userId}/capacities")
                ->collect()
        );
    }

    /**
     * The totals are always unaffected by the grouping and come with every scope=totals report.
     * The groupings provided via the group_by parameter produce **additional** output which we don't need in all scenarios.
     * Omitting the group_by parameter will return all the possible groupings, so we **explicitly** set it to an empty string.
     *
     * @throws ConnectionException
     */
    public function getTotalHoursForPeriod(PeriodData $period): DurationData
    {
        return DurationData::from($this->client
            ->get("{$this->accountId}/reports/filter", [
                'since' => $period->since?->format('Y-m-d'),
                'until' => $period->until?->format('Y-m-d'),
                'user_ids' => 'self',
                'group_by' => '',
                'scope' => 'totals',
            ])->json('totals.duration')
        );
    }

    /**
     * @return Collection<int, DailyDurationData>
     *
     * @throws ConnectionException
     */
    public function getDailyTotalHoursForPeriod(PeriodData $period): Collection
    {
        return DailyDurationData::collect($this->client
            ->get("{$this->accountId}/reports/filter", [
                'since' => $period->since?->format('Y-m-d'),
                'until' => $period->until?->format('Y-m-d'),
                'user_ids' => 'self',
                'group_by' => 'days',
                'scope' => 'totals',
            ])->collect('days')
        );
    }

    /**
     * @return Collection<int, EventData>
     *
     * @throws ConnectionException
     */
    public function getEventsForPeriod(PeriodData $period): Collection
    {
        $events = collect();

        foreach ($this->yieldEventBatchesForPeriod($period) as $batch) {
            $realEventsFromBatch = $batch->reject(fn (EventData $event): bool => $event->deleted || $event->draft);

            $events->push(...$realEventsFromBatch);
        }

        return $events;
    }

    /**
     * @return Generator<int, Collection<int, EventData>>
     *
     * @throws ConnectionException
     */
    private function yieldEventBatchesForPeriod(PeriodData $period): Generator
    {
        $page = 1;

        do {
            $batch = EventData::collect($this->client
                ->get("{$this->accountId}/hours", [
                    'since' => $period->since?->format('Y-m-d'),
                    'upto' => $period->until?->format('Y-m-d'),
                    'user_id' => $this->userId,
                    'per_page' => self::EVENTS_PER_PAGE,
                    'page' => $page,
                    'sort' => 'day',
                    'order' => 'asc',
                ])->collect()
            );

            yield $batch;

            $page++;

        } while ($batch->count() === self::EVENTS_PER_PAGE);
    }
}
