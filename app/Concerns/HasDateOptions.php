<?php

namespace App\Concerns;

use App\DataTransferObjects\PeriodData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;

trait HasDateOptions
{
    /**
     * @throws ConnectionException
     */
    private function parsePeriodOptions(): ?PeriodData
    {
        $since = $this->parseDateOption(
            option: 'since',
            value: $this->option('since')
                ?? config('timely.since')
                ?? $this->timely->getCreationDate()
        );

        $until = $this->parseDateOption(
            option: 'until',
            value: $this->option('until')
                ?? CarbonImmutable::yesterday()
        );

        if ($since === null || $until === null) {
            return null;
        }

        return PeriodData::fromBoundaries($since, $until);
    }

    private function parseDateOption(string $option, string|CarbonImmutable $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        if (! CarbonImmutable::hasFormat($value, 'Y-m-d')) {
            $this->error("Cannot parse --{$option} date '{$value}'. All dates must be provided in the ISO 8601 format: YYYY-MM-DD");

            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value);
    }
}
