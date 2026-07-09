<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class OvertimeData extends Data
{
    public function __construct(
        public HoursData $logged,
        public HoursData $expected,
        public HoursData $balance,
    ) {}

    public static function fromHours(HoursData $logged, HoursData $expected): self
    {
        return new self(
            logged: $logged,
            expected: $expected,
            balance: HoursData::fromHours($logged->totalHours - $expected->totalHours),
        );
    }
}
