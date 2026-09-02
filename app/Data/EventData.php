<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @property Collection<int,TimestampData> $timestamps
 */
#[MapInputName(SnakeCaseMapper::class)]
class EventData extends Data
{
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class, format: '!Y-m-d')]
        public CarbonImmutable $day,
        public Collection $timestamps,
        public int $sequence,
        public bool $deleted,
        public bool $draft,
    ) {}
}
