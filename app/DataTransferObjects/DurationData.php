<?php

namespace App\DataTransferObjects;

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
        return $this->readable();
    }

    /**
     * Returns a human-readable string representation of the duration, e.g. `1h 30m`, `8h` or `24m`.
     * This format is used by Timely themselves throughout their app.
     *
     * @param  string  $glue  Glue between components.
     * @param  bool  $prefixPositive  Prefix positive durations with a `+` sign.
     */
    public function readable(
        bool $prefixPositive = false,
        string $glue = ' ',
    ): string {
        if ($this->totalSeconds === 0) {
            return '—';
        }

        $components = collect([
            'h' => $this->hours,
            'm' => $this->minutes,
        ])->filter()
            ->map(fn (int $value, string $unit): string => "{$value}{$unit}")
            ->implode($glue);

        $sign = $this->totalSeconds < 0 ? '-' : ($prefixPositive ? '+' : '');

        return "{$sign}{$components}";
    }

    /**
     * Returns a table-friendly string representation of the duration, e.g. `01h 30m`, `08h 00m` or `00h 24m`.
     *
     * @param  bool  $prefixPositive  Prefix positive durations with a `+` sign.
     */
    public function tabular(bool $prefixPositive = false): string
    {
        if ($this->totalSeconds === 0) {
            return '—';
        }

        $plus = $prefixPositive ? '+' : '';
        $hours = $this->totalSeconds < 0 ? $this->hours * -1 : $this->hours;

        return sprintf("%{$plus}02dh %02dm", $hours, $this->minutes);
    }
}
