<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

final readonly class TimelyService
{
    public function __construct(
        private readonly PendingRequest $client,
        private readonly int $accountId,
        private readonly int $userId,
    ) {}

    public function getTotalLoggedHours(?string $since = null): Collection
    {
        return $this->client->get("{$this->accountId}/reports/filter", [
            'scope' => 'totals',
            'group_by' => 'users',
            'user_ids' => $this->userId,
            'since' => $since,
            'until' => now()->format('Y-m-d'),
        ])->collect('totals.duration');
    }

    public function getCapacities(): Collection
    {
        return $this->client
            ->get("{$this->accountId}/users/{$this->userId}/capacities")
            ->collect();
    }
}
