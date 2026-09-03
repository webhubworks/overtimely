<?php

namespace App\Enums;

enum ReportMode: string
{
    case Events = 'events';

    case Totals = 'totals';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function settingInfo(string $value): string
    {
        $mode = self::from($value);

        return match ($mode) {
            self::Totals => 'Fetches total durations',
            self::Events => 'Fetches individual time entries and checks for overlaps',
        };
    }
}
