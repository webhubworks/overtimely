<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class DurationData extends Data
{
    public bool $isNegative;

    public function __construct(
        public int $hours,
        public int $minutes,
        public int $seconds,
        public string $formatted, // "h:i"
        public float $totalHours,
        public int $totalMinutes,
        public int $totalSeconds,
    ) {
        $this->isNegative = $this->totalSeconds < 0;
    }

    public static function fromTotalHours(float $totalHours): self
    {
        return self::fromTotalSeconds((int) round($totalHours * 3600));
    }

    public static function fromTotalSeconds(int $totalSeconds): self
    {
        $magnitude = abs($totalSeconds);

        $hours = intdiv($magnitude, 3600);
        $minutes = intdiv($magnitude % 3600, 60);
        $seconds = $magnitude % 60;

        return new self(
            hours: $hours,
            minutes: $minutes,
            seconds: $seconds,
            formatted: sprintf('%s%02d:%02d', $totalSeconds < 0 ? '-' : '', $hours, $minutes),
            totalHours: $totalSeconds / 3600,
            totalMinutes: intdiv($totalSeconds, 60),
            totalSeconds: $totalSeconds,
        );
    }

    public function toComponentsString(string $glue = ' '): string
    {
        $durationComponents = collect([
            'h' => $this->hours,
            'm' => $this->minutes,
            's' => $this->seconds,
        ])->filter()
            ->map(fn (int $value, string $unit): string => "{$value}{$unit}")
            ->implode($glue);

        $sign = $this->isNegative ? '-' : '';

        return $sign.$durationComponents;
    }
}
