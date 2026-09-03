<?php

namespace App\Concerns;

trait EnumValuesTrait
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
