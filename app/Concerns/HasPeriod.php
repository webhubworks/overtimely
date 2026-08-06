<?php

namespace App\Concerns;

use App\Commands\Get\BaseGetCommand;
use App\DataTransferObjects\PeriodData;
use App\Services\TimelyDataService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;

/**
 * @property TimelyDataService $timely
 * @property string $period
 */
trait HasPeriod
{
    protected const string SINCE_OPTION = 'since';

    protected const string UNTIL_OPTION = 'until';

    protected ?PeriodData $period;

    /**
     * @throws ConnectionException
     */
    protected function parsePeriod(): ?PeriodData
    {
        $since = $this->parsePeriodOption(
            self::SINCE_OPTION,
            $this->option(self::SINCE_OPTION)
                ?? config('timely.since')
                ?? $this->timely->getCreationDate()
        );

        $until = $this->parsePeriodOption(
            self::UNTIL_OPTION,
            $this->option(self::UNTIL_OPTION)
                ?? CarbonImmutable::yesterday()
        );

        if ($since === null || $until === null) {
            return null;
        }

        return PeriodData::fromBoundaries($since, $until);
    }

    protected function parsePeriodOption(string $option, string|CarbonImmutable $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();

        } catch (InvalidFormatException) {
            $this->error("Cannot parse --{$option} date '{$value}'. All dates must be provided in a format Carbon::parse() can understand.");

            return null;
        }
    }
}
