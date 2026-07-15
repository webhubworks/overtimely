<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\ParsesDateOptions;
use App\DataTransferObjects\BalanceData;
use App\DataTransferObjects\PeriodData;
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
    {--since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
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
    public function handle(): int
    {
        if (! $this->isAppConfigured()) {
            return self::FAILURE;
        }

        $timely = app(TimelyService::class);

        $since = $this->parseDateOption(
            '--since',
            $this->option('since') ?? config('timely.since') ?? $timely->getCreationDate()->format('Y-m-d'),
        );

        $until = $this->parseDateOption(
            '--until',
            $this->option('until') ?? CarbonImmutable::yesterday()->format('Y-m-d')
        );

        if ($since === null || $until === null) {
            return self::FAILURE;
        }

        $this->info('Fetching your capacities ...');
        $capacity = CapacityCalculationService::fromCapacities($timely->getCapacities());

        $this->info('Fetching your logged hours per month ...');
        $rows = PeriodData::monthlySplit($since, $until)->map(function (PeriodData $period) use ($timely, $capacity): array {
            $expected = $capacity->forPeriod($period->since, $period->until);
            $logged = $timely->getTotalLoggedHoursForPeriod($period->since, $period->until);
            $balance = BalanceData::fromOperands($logged, $expected);

            return [
                $period->since->format('F Y'),
                $balance->logged->toComponentsString(),
                $balance->expected->toComponentsString(),
                $balance->balance->toComponentsString(),
            ];
        });

        $this->newLine();
        $this->table(
            [
                'Month',
                'Logged Hours',
                'Expected Hours',
                'Overtime Balance',
            ],
            $rows->all(),
            config('display.table_style'),
        );

        return self::SUCCESS;
    }
}
