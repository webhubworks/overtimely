<?php

namespace App\Enums;

use App\Concerns\EnumValuesTrait;

enum FetchMode: string
{
    use EnumValuesTrait;

    case Events = 'events';

    case Totals = 'totals';

    public static function settingInfo(string $value): string
    {
        $mode = self::from($value);

        return match ($mode) {
            self::Totals => 'Fetches total durations',
            self::Events => 'Fetches individual time entries and merges overlapping durations',
        };
    }
}
