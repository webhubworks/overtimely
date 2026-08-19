<?php

use App\Enums\ConfigKey;
use App\Enums\TableStyle;

return [
    'table_style' => ConfigKey::TableStyle->envValue(TableStyle::Default->value),
];
