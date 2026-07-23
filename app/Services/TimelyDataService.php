<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\CurrentUserData;
use App\DataTransferObjects\DailyDurationData;
use App\DataTransferObjects\DurationData;
use App\DataTransferObjects\PeriodData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyDataService
{
    public function __construct(
        private PendingRequest $client,
        private int $accountId,
        private ?int $userId = null,
        private ?CarbonImmutable $createdAt = null,
    ) {}

    /**
     * The totals are always unaffected by the grouping and come with every scope=totals report. The group_by option produces additional output we don't need here.
     *
     * @throws ConnectionException
     */
    public function getTotalLoggedHoursForPeriod(PeriodData $period): DurationData
    {
        return DurationData::from(
            $this->client
                ->get("{$this->accountId}/reports/filter", [
                    'scope' => 'totals',
                    'user_ids' => 'self',
                    'group_by' => '',
                    'since' => $period->since?->format('Y-m-d'),
                    'until' => $period->until?->format('Y-m-d'),
                ])->json('totals.duration')
        );
    }

    /**
     * @return Collection<int, DailyDurationData>
     *
     * @throws ConnectionException
     */
    public function getDailyLoggedHoursForPeriod(PeriodData $period): Collection
    {
        return DailyDurationData::collect(
            $this->client
                ->get("{$this->accountId}/reports/filter", [
                    'scope' => 'totals',
                    'user_ids' => 'self',
                    'group_by' => 'days',
                    'since' => $period->since?->format('Y-m-d'),
                    'until' => $period->until?->format('Y-m-d'),
                ])
                ->collect('days')
        );
    }

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
}
