<?php

namespace App\Enums;

use App\Concerns\EnumValuesTrait;

enum TableStyle: string
{
    use EnumValuesTrait;

    case Default = 'default';

    case Compact = 'compact';

    case Markdown = 'markdown';

    case Borderless = 'borderless';

    case SymfonyStyleGuide = 'symfony-style-guide';

    case Box = 'box';

    case BoxDouble = 'box-double';
}
