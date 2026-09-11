<?php

namespace App\Services;

use App\Data\DurationData;
use App\Data\PeriodData;

abstract readonly class HoursService
{
    public function __construct(protected PeriodData $datasetPeriod) {}

    /**
     * Returns the logged hours for a given period if that period is included in the dataset.
     */
    abstract public function forPeriod(PeriodData $period): DurationData;
}
