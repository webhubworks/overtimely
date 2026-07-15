<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\ParsesDateOptions;
use App\Services\CapacityCalculationService;
use App\Services\TimelyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

class GetMonths extends Command
{
    use EnsuresAppConfiguration, ParsesDateOptions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:months
    {--since= : Start of the fetched report period. Defaults to 1970-01-01. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all months in the given period with their individual logged hours, expected hours and overtime balance.';

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle()
    {
        if (! $this->isAppConfigured()) {
            return self::FAILURE;
        }

        $since = $this->parseDateOption(
            '--since',
            $this->option('since') ?? config('timely.since') ?? '1970-01-01',
        );

        $until = $this->parseDateOption(
            '--until',
            $this->option('until') ?? CarbonImmutable::yesterday()->format('Y-m-d')
        );

        if ($since === null || $until === null) {
            return self::FAILURE;
        }

        $timely = app(TimelyService::class);

        $this->info('Fetching your capacities ...');
        $capacity = CapacityCalculationService::fromCapacities($timely->getCapacities());
    }
}
