<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class DurationData extends Data
{
    public function __construct(
        public int $hours,
        public int $minutes,
        public int $seconds,
        public float $totalHours,
        public int $totalMinutes,
        public int $totalSeconds,
    ) {}

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
            totalHours: $totalSeconds / 3600,
            totalMinutes: intdiv($totalSeconds, 60),
            totalSeconds: $totalSeconds,
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns a human-readable string representation of the duration, e.g. `12h 30m`, `8h` or `24m`.
     * This format is used by Timely themselves throughout their app.
     *
     * @param  bool  $prefixPositive  Prefix positive durations with a `+` sign.
     * @param  bool  $tabular  Use a tabular format, meaning components zero-padded to two digits and printed even if they are zero, e.g. `12h 30m`, `08h 00m` or `00h 24m`.
     * @param  string  $glue  Glue between components.
     * @param  string  $fallback  Fallback printed if this duration is zero.
     */
    public function toString(
        bool $prefixPositive = false,
        bool $tabular = false,
        string $glue = ' ',
        string $fallback = '—'
    ): string {
        if ($this->totalSeconds === 0) {
            return $fallback;
        }

        $sign = $this->totalSeconds < 0 ? '-' : ($prefixPositive ? '+' : '');

        if ($tabular) {
            return $sign.sprintf('%02dh%s%02dm', $this->hours, $glue, $this->minutes);
        }

        $components = collect([
            'h' => $this->hours,
            'm' => $this->minutes,
        ])->filter()
            ->map(fn (int $value, string $unit): string => "{$value}{$unit}")
            ->implode($glue);

        return $sign.$components;
    }
}
