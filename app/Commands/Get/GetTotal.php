<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\BalanceData;
use App\Services\CapacityCalculationService;
use App\Services\TimelyDataService;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

class GetTotal extends Command
{
    use EnsuresAppConfiguration, HasDateOptions;

    private TimelyDataService $timely;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:total
    {--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. Persistent custom default can be set via set:since. (Format: YYYY-MM-DD)}
    {--u|until= : End of the fetched report period. Defaults to yesterday if omitted. (Format: YYYY-MM-DD)}';

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

        $this->timely = app(TimelyDataService::class);

        $period = $this->parsePeriodOptions();

        if ($period->since === null || $period->until === null) {
            return self::FAILURE;
        }

        $this->info("Fetching data for period: $period");

        $this->info('Fetching and calculating your total capacity ...');
        $totalCapacity = CapacityCalculationService::fromCapacities($this->timely->getCapacities())
            ->forPeriod($period);

        $this->info('Fetching your total logged hours ...');
        $totalLoggedHours = $this->timely->getTotalLoggedHoursForPeriod($period);

        $balance = BalanceData::fromOperands($totalLoggedHours, $totalCapacity);

        $this->newLine();

        $this->table(
            [
                'Total Logged Hours',
                'Total Expected Hours',
                'Overtime Balance',
            ],
            [
                [
                    "$balance->logged",
                    "$balance->expected",
                    $balance->balance->readable(true),
                ],
            ],
            config('display.table_style'),
        );

        return self::SUCCESS;
    }
}
