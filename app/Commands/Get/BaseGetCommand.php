<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAuthentication;
use App\Concerns\HasPeriod;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityService;
use App\Services\TimelyDataService;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

abstract class BaseGetCommand extends Command
{
    use EnsuresAuthentication, HasPeriod;

    protected TimelyDataService $timely;

    protected CapacityService $capacity;

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle()
    {
        if (! $this->isAuthenticated()) {
            return self::FAILURE;
        }

        $this->timely = app(TimelyDataService::class);

        $this->period = $this->parsePeriod();

        if ($this->period === null) {
            $this->newLine();
            $this->error('Could not determine a data-fetching period.');

            return self::FAILURE;
        }

        $this->line("Fetching your data for the period of $this->period");

        $this->line('Fetching your capacities ...');

        $capacities = $this->timely->getCapacities();

        $this->capacity = CapacityService::fromCapacities($capacities);
    }
}
