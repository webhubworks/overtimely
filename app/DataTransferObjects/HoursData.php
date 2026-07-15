<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class HoursData extends Data
{
    public function __construct(
        public int $hours,
        public int $minutes,
        public int $seconds,
        public string $formatted, // "h:i"
        public float $totalHours,
        public float $totalMinutes,
        public int $totalSeconds,
    ) {}

    /**
     * Build the same field set the Timely `duration` object provides from a
     * plain hour total, so computed figures (expected hours, balance) share one
     * DTO with the API-hydrated logged hours.
     *
     * Handles negatives: the hours/minutes/seconds components carry the
     * magnitude and the sign lives in `formatted` and the signed totals.
     */
    public static function fromTotalHours(float $totalHours): self
    {
        $totalSeconds = (int) round($totalHours * 3600);
        $magnitude = abs($totalSeconds);

        $hours = intdiv($magnitude, 3600);
        $minutes = intdiv($magnitude % 3600, 60);
        $seconds = $magnitude % 60;

        return new self(
            hours: $hours,
            minutes: $minutes,
            seconds: $seconds,
            formatted: sprintf('%s%02d:%02d', $totalSeconds < 0 ? '-' : '', $hours, $minutes),
            totalHours: $totalHours,
            totalMinutes: $totalSeconds / 60,
            totalSeconds: $totalSeconds,
        );
    }
}
