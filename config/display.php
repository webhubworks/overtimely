<?php

use App\Enums\Setting;
use App\Enums\TableStyle;

return [
    'table_style' => Setting::TableStyle->envValue(TableStyle::Default->value),
];
