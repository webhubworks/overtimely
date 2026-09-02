<?php

namespace App\Services;

use App\Data\CapacityData;
use App\Data\CurrentUserData;
use App\Data\DailyDurationData;
use App\Data\DurationData;
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
    public function getTotalLoggedHoursForPeriod(PeriodData $period): DurationData
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
    public function getDailyTotalLoggedHoursForPeriod(PeriodData $period): Collection
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
     * @throws ConnectionException
     */
    public function getEventsForPeriod(PeriodData $period): Collection
    {
        $events = collect();

        foreach ($this->yieldEventsForPeriod($period) as $batch) {
            foreach ($batch as $event) {
                if ($event['deleted'] ?? false) {
                    continue;
                }

                $events->push($event);
            }
        }

        return $events;
    }

    /**
     * @return Generator<int, Collection<int, array<string, mixed>>>
     *
     * @throws ConnectionException
     */
    public function yieldEventsForPeriod(PeriodData $period): Generator
    {
        $page = 1;

        do {
            $batch = $this->client
                ->get("{$this->accountId}/hours", array_filter([
                    'since' => $period->since->format('Y-m-d'),
                    'upto' => $period->until->format('Y-m-d'),
                    'user_id' => $this->userId,
                    'per_page' => self::EVENTS_PER_PAGE,
                    'page' => $page,
                    'sort' => 'day',
                    'order' => 'asc',
                ], fn (mixed $value): bool => $value !== null))
                ->collect();

            yield $batch;

            $page++;

        } while ($batch->count() === self::EVENTS_PER_PAGE);
    }
}
