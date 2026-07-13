<?php

return [
    'base_url' => env('TIMELY_API_URL', 'https://api.timelyapp.com/1.1/'),
    'token' => env('TIMELY_API_TOKEN'),
    'timeout' => env('TIMELY_API_TIMEOUT', 15),
    'account_id' => env('TIMELY_ACCOUNT_ID'),
    'user_id' => env('TIMELY_USER_ID'),
    'since' => env('TIMELY_SINCE'),
];
