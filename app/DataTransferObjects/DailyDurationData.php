<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class DailyDurationData extends Data
{
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class, format: '!Y-m-d')]
        public CarbonImmutable $day,
        public DurationData $duration,
    ) {}
}
