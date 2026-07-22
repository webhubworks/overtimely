<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAppConfiguration;
use App\Concerns\HasDateOptions;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityService;
use App\Services\TimelyDataService;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

class GetBaseCommand extends Command
{
    use EnsuresAppConfiguration, HasDateOptions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:base';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a base command class meant for the get:* commands to extend. It is not intended to be run at all and only visible in local development.';

    protected TimelyDataService $timely;

    protected CapacityService $capacity;

    protected ?PeriodData $period;

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

        $this->timely = app(TimelyDataService::class);

        $this->period = $this->parsePeriodOptions();

        if ($this->period === null) {
            $this->newLine();
            $this->error('Could not determine a data-fetching period.');

            return self::FAILURE;
        }

        $this->info("Fetching your data for the period of $this->period");

        $this->info('Fetching your capacities ...');

        $capacities = $this->timely->getCapacities();

        $this->capacity = CapacityService::fromCapacities($capacities);
    }
}
