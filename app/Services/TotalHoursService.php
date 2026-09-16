<?php

namespace App\Services;

use App\Data\DurationData;
use App\Data\PeriodData;
use Illuminate\Http\Client\ConnectionException;

final class TotalHoursService extends HoursService
{
    public function __construct(
        private readonly ?DurationData $totalHours,
        PeriodData $datasetPeriod
    ) {
        parent::__construct($datasetPeriod);
    }

    public static function from(DurationData $totalHours, PeriodData $datasetPeriod): self
    {
        return new self($totalHours, $datasetPeriod);
    }

    /**
     * @throws ConnectionException
     */
    public function forPeriod(PeriodData $period): DurationData
    {
        if (! $this->datasetPeriod->includes($period)) {
            return DurationData::zero();
        }

        return $this->totalHours;
    }
}
