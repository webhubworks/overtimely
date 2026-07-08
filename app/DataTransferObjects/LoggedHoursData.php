<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class LoggedHoursData extends Data
{
    public function __construct(
        public int $hours,
        public int $minutes,
        public int $seconds,
        public string $formatted,// "h:i"
        public float $totalHours,
        public int $totalSeconds,
        public int $totalMinutes,
    ) {}
}
