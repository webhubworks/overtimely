<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ExpectedHoursData extends Data
{
    public function __construct(
        public int $hours,
        public int $minutes,
        public int $seconds,
        public string $formatted,        // "h:i", e.g. "40:00"
        public float $totalHours,
        public int $totalSeconds,
        public int $totalMinutes,
    ) {}

    /**
     * Build the same field set as LoggedHoursData from a computed hour total,
     * so expected and logged hours can be displayed the same way.
     */
    public static function fromHours(float $totalHours): self
    {
        $totalSeconds = (int) round($totalHours * 3600);

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        return new self(
            hours: $hours,
            minutes: $minutes,
            seconds: $seconds,
            formatted: sprintf('%02d:%02d', $hours, $minutes),
            totalHours: round($totalHours, 2),
            totalSeconds: $totalSeconds,
            totalMinutes: intdiv($totalSeconds, 60),
        );
    }
}
