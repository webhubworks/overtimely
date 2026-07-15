<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * This reflects the data returned by the Timely API.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CapacityData extends Data
{
    public function __construct(
        public ?int $id,                                            // null for the account default capacity
        public float $weeklyCapacity,
        public float $dailyCapacity,
        public ?string $weekdays,                                   // legacy format, e.g. "MO,TU,WE,TH,FR"
        public string $workDays,                                    // "MON,TUE,WED,THU,FRI"
        public ?int $totalWorkingDays,                              // null for open-ended capacities
        public int $weeklyWorkingDays,
        public bool $current,                                       // true = active capacity (has no end date)
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public CarbonImmutable $startDate,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public ?CarbonImmutable $endDate,                           // null for open-ended capacities
    ) {}
}
