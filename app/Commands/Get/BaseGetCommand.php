<?php

namespace App\Commands\Get;

use App\Concerns\EnsuresAuthentication;
use App\DataTransferObjects\PeriodData;
use App\Services\CapacityService;
use App\Services\TimelyDataService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;

abstract class BaseGetCommand extends Command
{
    use EnsuresAuthentication;

    protected array $periodOptions = [
        'since' => '{--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. A persistent custom default can be set using the set:since command.}',
        'until' => '{--u|until= : End of the fetched report period. Defaults to yesterday if omitted.}',
    ];

    protected CapacityService $capacity;

    protected ?PeriodData $period;

    protected TimelyDataService $timely;

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    final public function handle(): int
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

        return $this->get();
    }

    /**
     * Called by `handle()` for actual child-specific command execution.
     */
    abstract protected function get(): int;

    /**
     * @throws ConnectionException
     */
    private function parsePeriod(): ?PeriodData
    {
        [$sinceOption, $untilOption] = array_keys($this->periodOptions);

        $since = $this->parsePeriodOption(
            $sinceOption,
            $this->option($sinceOption)
                ?? config('timely.since')
                ?? $this->timely->getCreationDate()
        );

        $until = $this->parsePeriodOption(
            $untilOption,
            $this->option($untilOption)
                ?? CarbonImmutable::yesterday()
        );

        if ($since === null || $until === null) {
            return null;
        }

        return PeriodData::fromBoundaries($since, $until);
    }

    private function parsePeriodOption(string $option, string|CarbonImmutable $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();

        } catch (InvalidFormatException) {
            $this->error("Cannot parse $option date '$value' | All dates must be provided in a format Carbon::parse() can understand.");

            return null;
        }
    }
}
