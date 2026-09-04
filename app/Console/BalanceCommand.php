<?php

namespace App\Console;

use App\Concerns\EnsuresAuthentication;
use App\Data\PeriodData;
use App\Enums\FetchMode;
use App\Enums\FetchPeriod;
use App\Enums\Setting;
use App\Services\CapacityService;
use App\Services\DailyTotalHoursService;
use App\Services\EventHoursService;
use App\Services\HoursService;
use App\Services\TimelyDataService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

abstract class BalanceCommand extends Command
{
    use EnsuresAuthentication;

    protected TimelyDataService $timely;

    protected CapacityService $capacity;

    protected HoursService $hours;

    protected ?PeriodData $period;

    protected FetchMode $mode;

    public function __construct()
    {
        $this->signature = trim($this->signature.' '.self::baseOptions());

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

        $this->mode = $this->parseMode();

        $this->info("Fetching your data for the period of $this->period using '{$this->mode->value}' mode.");

        $capacities = $this->timely->getCapacities();
        $this->capacity = CapacityService::fromCapacities($capacities);

        $this->hours = $this->buildHoursService();

        return $this->report();
    }

    abstract protected function report(): int;

    abstract protected function buildHoursService(): HoursService;

    /**
     * @throws ConnectionException
     */
    protected function buildDailyTotalHoursService(): DailyTotalHoursService
    {
        $dailyTotalHours = $this->timely->getDailyTotalHoursForPeriod($this->period);

        return DailyTotalHoursService::fromDailyDurations($dailyTotalHours);
    }

    /**
     * @throws ConnectionException
     */
    protected function buildEventHoursService(): EventHoursService
    {
        $events = $this->timely->getEventsForPeriod($this->period);

        return EventHoursService::fromEvents($events);
    }

    private static function baseOptions(): string
    {
        $formatHint = Setting::DATE_FORMATS_HINT;
        $modeSettingName = Setting::ReportFetchMode->kebabName();
        $sinceSettingName = Setting::ReportSince->kebabName();
        $untilSettingName = Setting::ReportUntil->kebabName();
        $presets = implode(', ', FetchPeriod::values());
        $modes = implode(', ', FetchMode::values());

        return implode(' ', [
            "{--m|mode= : The report fetch mode. One of [$modes]. Defaults to 'totals'. Run 'config:set $modeSettingName' to set a custom default and see what each mode does.}",
            "{--s|since= : Start of the fetched report period. Defaults to the date your Timely account was created. A persistent custom default can be set with 'config:set $sinceSettingName'. $formatHint}",
            "{--u|until= : End of the fetched report period. Defaults to yesterday if omitted. A persistent custom default can be set with 'config:set $untilSettingName'. $formatHint}",
            "{--p|period= : A preset report period, used instead of --since and --until. One of [$presets]. The this-* presets run up to today, so hours you have not logged yet count towards minus hours.}",
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
                ?? Setting::ReportSince->getConfigValue()
                ?? $this->timely->getCreationDate()
        );

        $until = $this->parseDateOption(
            '--until',
            $this->option('until')
                ?? Setting::ReportUntil->getConfigValue()
                ?? CarbonImmutable::yesterday()
        );

        if ($since === null || $until === null) {
            return null;
        }

        if ($since->greaterThan($until)) {
            $this->warn("--since ({$since->format('Y-m-d')}) cannot be after --until ({$until->format('Y-m-d')}).");

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

        $period = FetchPeriod::tryFrom($preset);

        if ($period === null) {
            $this->warn("Unknown period '$preset'. Choose one of: ".implode(', ', FetchPeriod::values()));

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
            $this->warn("Cannot parse $option '$value' ".Setting::DATE_FORMATS_HINT);

            return null;
        }
    }

    private function parseMode(): FetchMode
    {
        return FetchMode::tryFrom($this->option('mode') ?? '')
            ?? FetchMode::tryFrom(Setting::ReportFetchMode->getConfigValue(''))
            ?? FetchMode::Totals;
    }
}
