<?php

namespace App\Enums;

use App\Data\PeriodData;
use Carbon\CarbonImmutable;

enum ReportPeriod: string
{
    case ThisWeek = 'this-week';

    case LastWeek = 'last-week';

    case ThisMonth = 'this-month';

    case LastMonth = 'last-month';

    case ThisYear = 'this-year';

    case LastYear = 'last-year';

    public function toPeriodData(): PeriodData
    {
        $today = CarbonImmutable::today();
        $lastWeek = $today->startOfWeek()->subWeek();
        $lastMonth = $today->startOfMonth()->subMonth();
        $lastYear = $today->startOfYear()->subYear();

        return match ($this) {
            self::ThisWeek => PeriodData::fromBoundaries($today->startOfWeek(), $today),
            self::LastWeek => PeriodData::fromBoundaries($lastWeek, $lastWeek->endOfWeek()),
            self::ThisMonth => PeriodData::fromBoundaries($today->startOfMonth(), $today),
            self::LastMonth => PeriodData::fromBoundaries($lastMonth, $lastMonth->endOfMonth()),
            self::ThisYear => PeriodData::fromBoundaries($today->startOfYear(), $today),
            self::LastYear => PeriodData::fromBoundaries($lastYear, $lastYear->endOfYear()),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
