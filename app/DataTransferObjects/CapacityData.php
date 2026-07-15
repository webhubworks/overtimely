<?php

namespace App\DataTransferObjects;

use App\Casts\CollectionFromSeparatedStringCast;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
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
        public ?int $id,                                            // Capacity ID. Null for default capacity
        public float $weeklyCapacity,                               // Specifies the user's weekly hour capacity. The default is account's weekly capacity. Can only have a decimal place of .5 (e.g. 3.5 hours)
        public float $dailyCapacity,                                // Specifies the user's daily hour capacity
        #[WithCast(CollectionFromSeparatedStringCast::class)]
        public ?Collection $weekdays,                               // Legacy weekday format. Example: 'MO,TU,WE,TH,FR'
        #[WithCast(CollectionFromSeparatedStringCast::class)]
        public Collection $workDays,                                // Comma-separated working days. Example: 'MON,TUE,WED,THU,FRI'
        public ?int $totalWorkingDays,                              // Total working days in the capacity period. Null for open-ended capacities
        public int $weeklyWorkingDays,                              // Specifies the number of user's weekly working days
        public bool $current,                                       // True if this is the active capacity (no end date)
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public CarbonImmutable $startDate,                          // ISO8601 start date
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public ?CarbonImmutable $endDate,                           // ISO8601 end date. Null for open-ended capacities
    ) {}
}
