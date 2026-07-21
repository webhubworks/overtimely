<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class PeriodBalanceData extends Data
{
    public function __construct(
        public PeriodData $period,
        public BalanceData $balance,
    ) {}
}
