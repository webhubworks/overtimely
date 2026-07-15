<?php

namespace App\Concerns;

use Carbon\CarbonImmutable;

trait ParsesDateOptions
{
    protected function parseDateOption(string $option, string $value): ?CarbonImmutable
    {
        if (! CarbonImmutable::hasFormat($value, 'Y-m-d')) {
            $this->error("Cannot parse {$option} date '{$value}'. All dates must be provided in the ISO 8601 format: YYYY-MM-DD");

            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value);
    }
}
