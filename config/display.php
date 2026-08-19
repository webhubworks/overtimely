<?php

use App\Enums\ConfigKey;

return [
    'table_style' => ConfigKey::TableStyle->envValue('default'),
];
