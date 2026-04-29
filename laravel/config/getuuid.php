<?php

return [
    'api_name' => 'UUID Generator API',
    'api_version' => '1.0.0',
    'rate' => [
        'limit' => (int) env('GETUUID_RATE_LIMIT', 60),
        'window_seconds' => (int) env('GETUUID_RATE_WINDOW_SECONDS', 60),
    ],
];
