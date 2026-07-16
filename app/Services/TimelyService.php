<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\CurrentUserData;
use App\DataTransferObjects\DurationData;
use App\DataTransferObjects\PeriodData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyService
{
    public function __construct(
        private PendingRequest $client,
        private int $accountId,
        private ?int $userId = null,
        private ?CarbonImmutable $createdAt = null,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function getTotalLoggedHoursForPeriod(PeriodData $period): DurationData
    {
        return DurationData::from(
            $this->client
                ->get("{$this->accountId}/reports/filter", [
                    'scope' => 'totals',
                    'group_by' => 'users',
                    'user_ids' => $this->userId,
                    'since' => $period->since?->format('Y-m-d'),
                    'until' => $period->until?->format('Y-m-d'),
                ])->json('totals.duration')
        );
    }

    public function getCurrentUser(): CurrentUserData
    {
        return CurrentUserData::fromApi(
            $this->client->get("{$this->accountId}/users/current")->json()
        );
    }

    public function getCreationDate(): CarbonImmutable
    {
        if ($this->createdAt !== null) {
            return $this->createdAt;
        }

        return $this->getCurrentUser()->createdAt->startOfDay();
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
