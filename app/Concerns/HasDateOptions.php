<?php

namespace App\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;

trait HasDateOptions
{
    /**
     * @throws ConnectionException
     */
    private function parsePeriodOptions(): array
    {
        return [
            $this->parseDateOption(
                'since',
                $this->option('since')
                    ?? config('timely.since')
                    ?? $this->timely->getCreationDate()
            ),
            $this->parseDateOption(
                'until',
                $this->option('until') ?? CarbonImmutable::yesterday()
            ),
        ];
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
