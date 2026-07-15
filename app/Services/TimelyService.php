<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\HoursData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyService
{
    public function __construct(
        private PendingRequest $client,
        private int $accountId,
        private int $userId,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function getTotalLoggedHoursForPeriod(?CarbonImmutable $since = null, ?CarbonImmutable $until = null): HoursData
    {
        return HoursData::from(
            $this->client
                ->get("{$this->accountId}/reports/filter", [
                    'scope' => 'totals',
                    'group_by' => 'users',
                    'user_ids' => $this->userId,
                    'since' => $since?->format('Y-m-d'),
                    'until' => $until?->format('Y-m-d'),
                ])->json('totals.duration')
        );
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
