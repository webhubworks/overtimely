<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class MonthlyBalanceData extends Data
{
    public function __construct(
        public PeriodData $month,
        public BalanceData $balance,
    ) {}
}
