<?php

namespace App\Services;

use App\DataTransferObjects\CapacityData;
use App\DataTransferObjects\LoggedHoursData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyService
{
    public function __construct(
        private readonly PendingRequest $client,
        private readonly int $accountId,
        private readonly int $userId,
    ) {}

    public function getTotalLoggedHours(?string $since = null): LoggedHoursData
    {
        return LoggedHoursData::from(
            $this->client->get("{$this->accountId}/reports/filter", [
                'scope' => 'totals',
                'group_by' => 'users',
                'user_ids' => $this->userId,
                'since' => $since,
                'until' => now()->subDay()->format('Y-m-d'), // yesterday
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
