<?php

namespace App\Enums;

enum TableStyle: string
{
    case Default = 'default';

    case Compact = 'compact';

    case Markdown = 'markdown';

    case Borderless = 'borderless';

    case SymfonyStyleGuide = 'symfony-style-guide';

    case Box = 'box';

    case BoxDouble = 'box-double';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
