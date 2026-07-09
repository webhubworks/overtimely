<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class OvertimeData extends Data
{
    public function __construct(
        public LoggedHoursData $logged,
        public ExpectedHoursData $expected,
        public float $balance,
    ) {}

    public static function fromHours(LoggedHoursData $logged, ExpectedHoursData $expected): self
    {
        return new self(
            logged: $logged,
            expected: $expected,
            balance: round($logged->totalHours - $expected->totalHours, 1),
        );
    }
}
