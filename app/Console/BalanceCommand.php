<?php

namespace App\Console;

use App\Concerns\EnsuresAuthentication;
use App\DataTransferObjects\PeriodData;
use App\Enums\ConfigKey;
use App\Enums\ReportPeriod;
use App\Services\CapacityService;
use App\Services\TimelyDataService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

abstract class BalanceCommand extends Command
{
    use EnsuresAuthentication;

    protected CapacityService $capacity;

    protected ?PeriodData $period;

    protected TimelyDataService $timely;

    public function __construct()
    {
        $this->signature = trim($this->signature.' '.self::periodOptions());

        parent::__construct();
    }

    /**
     * @throws ConnectionException
     */
    final public function handle(): int
    {
        if (! $this->isAuthenticated()) {
            return self::FAILURE;
        }

        try {
            $this->timely = app(TimelyDataService::class);
        } catch (RuntimeException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->period = $this->parsePeriod();

        if ($this->period === null) {
            $this->newLine();
            $this->error('Could not determine a valid data-fetching period.');

            return self::FAILURE;
        }

        $this->line("Fetching your data for the period of $this->period");

        $this->line('Fetching your capacities ...');

        $capacities = $this->timely->getCapacities();

        $this->capacity = CapacityService::fromCapacities($capacities);

        return $this->report();
    }

    abstract protected function report(): int;

    private static function periodOptions(): string
    {
        $presets = implode(', ', ReportPeriod::values());

        return implode(' ', [
            '{--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. A persistent custom default can be set with config:set since.}',
            '{--u|until= : End of the fetched report period. Defaults to yesterday if omitted.}',
            "{--p|period= : A preset report period, used instead of --since and --until. One of $presets. The this-* presets run up to today, so hours you have not logged yet count towards minus hours.}",
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function parsePeriod(): ?PeriodData
    {
        if (filled($this->option('period'))) {
            return $this->parsePreset($this->option('period'));
        }

        $since = $this->parseDateOption(
            '--since',
            $this->option('since')
                ?? ConfigKey::Since->getConfigValue()
                ?? $this->timely->getCreationDate()
        );

        $until = $this->parseDateOption(
            '--until',
            $this->option('until')
                ?? CarbonImmutable::yesterday()
        );

        if ($since === null || $until === null) {
            return null;
        }

        if ($since->greaterThan($until)) {
            $this->warn("--since (".$since->format('Y-m-d').") cannot be after --until (".$until->format('Y-m-d').").");

            return null;
        }

        return PeriodData::fromBoundaries($since, $until);
    }

    private function parsePreset(string $preset): ?PeriodData
    {
        if (filled($this->option('since')) || filled($this->option('until'))) {
            $this->warn('The --period option cannot be combined with --since or --until.');

            return null;
        }

        $period = ReportPeriod::tryFrom($preset);

        if ($period === null) {
            $this->warn("Unknown period '$preset'. Choose one of: ".implode(', ', ReportPeriod::values()));

            return null;
        }

        return $period->toPeriodData();
    }

    private function parseDateOption(string $option, string|CarbonImmutable $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();

        } catch (InvalidFormatException) {
            $this->warn("Cannot parse $option '$value' | For supported date formats see: https://www.php.net/manual/en/datetime.formats.php");

            return null;
        }
    }
}
