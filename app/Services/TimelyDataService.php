<?php

namespace App\Services;

use App\Data\CapacityData;
use App\Data\CurrentUserData;
use App\Data\DailyDurationData;
use App\Data\DurationData;
use App\Data\EventData;
use App\Data\PeriodData;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class TimelyDataService
{
    /**
     * Timely's API has a maximum of 5000 events per page.
     * We leverage that to reduce the number of requests to a minimum.
     */
    private const int EVENTS_PER_PAGE = 5000;

    /**
     * The maximum number of event chunks to fetch concurrently.
     * Past 12 there is no significant performance gain.
     */
    private const int MAX_CONCURRENT_CHUNKS = 12;

    /**
     * The aimed length in days of each period chunk.
     */
    private const int TARGET_DAYS_PER_CHUNK = 31;

    public function __construct(
        private ?string $accessToken,
        private int $accountId,
        private ?int $userId = null,
    ) {}

    private function client(Factory|Pool $factoryOrPool): PendingRequest
    {
        /** @var PendingRequest $request */
        $request = $factoryOrPool
            ->baseUrl(config('timely.api.base_url'))
            ->withToken($this->accessToken)
            ->connectTimeout(config('timely.api.connect_timeout'))
            ->timeout(config('timely.api.timeout'))
            ->retry(config('timely.api.attempts'), 1000)
            ->throw()
            ->acceptJson()
            ->withHeaders([
                'Accept-Encoding' => 'gzip',
            ]);

        return $request;
    }

    /**
     * @throws ConnectionException
     */
    public function getCurrentUser(): CurrentUserData
    {
        return CurrentUserData::from(
            $this->client(Http::getFacadeRoot())
                ->get("$this->accountId/users/current")
                ->json()
        );
    }

    /**
     * @return Collection<int, CapacityData>
     *
     * @throws ConnectionException
     */
    public function getCapacities(): Collection
    {
        return CapacityData::collect(
            $this->client(Http::getFacadeRoot())
                ->get("$this->accountId/users/$this->userId/capacities")
                ->collect()
        );
    }

    /**
     * The totals are always unaffected by the grouping and come with every `scope=totals` report.
     * The groupings provided via the `group_by` parameter produce **additional** output which we don't need in all scenarios.
     * Omitting the `group_by` parameter will return all the possible groupings, so we **explicitly** set it to an empty string.
     *
     * @throws ConnectionException
     */
    public function getTotalHoursForPeriod(PeriodData $period): DurationData
    {
        return DurationData::from($this->client(Http::getFacadeRoot())
            ->get("$this->accountId/reports/filter", [
                'since' => $period->since->format('Y-m-d'),
                'until' => $period->until->format('Y-m-d'),
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
        return DailyDurationData::collect($this->client(Http::getFacadeRoot())
            ->get("$this->accountId/reports/filter", [
                'since' => $period->since->format('Y-m-d'),
                'until' => $period->until->format('Y-m-d'),
                'user_ids' => 'self',
                'group_by' => 'days',
                'scope' => 'totals',
            ])->collect('days')
        );
    }

    /**
     * @return Collection<int, EventData>
     *
     * @throws ConnectionException|Throwable
     */
    public function getEventsForPeriod(PeriodData $period): Collection
    {
        $pendingChunks = $this->chunkPeriod($period)
            ->map(fn (PeriodData $chunk): array => [
                'period' => $chunk,
                'page' => 1,
            ])->values();

        $eventBatches = collect();

        while ($pendingChunks->isNotEmpty()) {
            $pooledResponses = Http::pool(fn (Pool $pool): array => $pendingChunks
                ->map(fn (array $chunk): PromiseInterface => $this->getEventBatchForPeriod($pool, $chunk['period'], $chunk['page']))
                ->all());

            $chunksWithMoreThanOnePage = collect();

            foreach ($pooledResponses as $index => $response) {
                if ($response instanceof Throwable) {
                    throw $response;
                }

                $events = $response->collect();

                $eventBatches->push(EventData::collect($events));

                if ($events->count() === self::EVENTS_PER_PAGE) {
                    $chunksWithMoreThanOnePage->push([
                        'period' => $pendingChunks[$index]['period'],
                        'page' => $pendingChunks[$index]['page'] + 1,
                    ]);
                }
            }

            $pendingChunks = $chunksWithMoreThanOnePage->values();
        }

        return $eventBatches->collapse();
    }

    /**
     * @return Collection<int, PeriodData>
     */
    private function chunkPeriod(PeriodData $period): Collection
    {
        $chunkCount = min(self::MAX_CONCURRENT_CHUNKS, (int) ceil($period->totalDays() / self::TARGET_DAYS_PER_CHUNK));

        return $period->chunks($chunkCount);
    }

    /**
     * @throws ConnectionException
     */
    private function getEventBatchForPeriod(Pool $pool, PeriodData $period, int $page): PromiseInterface
    {
        return $this->client($pool)
            ->get("$this->accountId/hours", [
                'since' => $period->since->format('Y-m-d'),
                'upto' => $period->until->format('Y-m-d'),
                'user_id' => $this->userId,
                'per_page' => self::EVENTS_PER_PAGE,
                'page' => $page,
                'sort' => 'day',
                'order' => 'asc',
            ]);
    }
}
