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
     * Returns a human-readable string representation of the duration, e.g. `1h 30m`
     * This format is used by Timely themselves throughout their app.
     *
     * @param  string  $glue  Glue between components.
     * @param  bool  $prefixPositive  Prefix positive durations with a `+` sign.
     */
    public function readable(
        string $glue = ' ',
        bool $prefixPositive = false,
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

        $sign = $this->isNegative ? '-' : ($prefixPositive ? '+' : '');

        return "{$sign}{$components}";
    }

    /**
     * Takes a `sprintf` format and injects 2 digits for hours and minutes as its values.
     */
    public function format(string $format): string
    {
        if ($this->totalSeconds === 0) {
            return '—';
        }

        /**
         * Hours derived from the total hours here instead of the existing hours component
         * because they carry the duration's sign, which is needed if the passed format
         * contains the `+` flag, which prefixes positive numbers with a `+` sign.
         */
        $hours = (int) $this->totalHours;

        return sprintf($format, $hours, $this->minutes);
    }
}
