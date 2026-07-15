<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\ParsesDateOptions;
use App\DataTransferObjects\BalanceData;
use App\Services\CapacityCalculationService;
use App\Services\TimelyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

class GetTotal extends Command
{
    use EnsuresAppConfiguration, ParsesDateOptions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total
    {--since= : Start of the fetched report period. Defaults to 1970-01-01. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches your capacities and total logged hours for the period of SINCE to UNTIL and calculates the total overtime balance.';

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

        $this->info('Fetching and calculating your total capacity ...');
        $totalCapacity = CapacityCalculationService::fromCapacities($timely->getCapacities())
            ->forPeriod($since, $until);

        $this->info('Fetching your total logged hours ...');
        $totalLoggedHours = $timely->getTotalLoggedHoursForPeriod($since, $until);

        $balance = BalanceData::fromOperands($totalLoggedHours, $totalCapacity);

        $this->newLine();
        $this->info("Your overtime for the period from {$since->format('jS \o\f F Y')} to {$until->format('jS \o\f F Y')} is {$balance->balance->formatted}h.");
        $this->newLine();

        $this->table(
            [
                'Total Logged Hours',
                'Total Expected Hours',
                'Overtime Balance',
            ],
            [
                [
                    $balance->logged->toComponentsString(),
                    $balance->expected->toComponentsString(),
                    $balance->balance->toComponentsString(),
                ],
            ],
            config('display.table_style'),
        );

        return self::SUCCESS;
    }
}
