<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class BalanceData extends Data
{
    public function __construct(
        public DurationData $logged,
        public DurationData $expected,
        public DurationData $balance,
    ) {}

    public static function fromOperands(DurationData $logged, DurationData $expected): self
    {
        return new self(
            logged: $logged,
            expected: $expected,
            balance: DurationData::fromTotalHours($logged->totalHours - $expected->totalHours),
        );
    }
}
