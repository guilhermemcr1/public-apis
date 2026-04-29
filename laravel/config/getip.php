<?php

return [
    'api_name' => 'IP Detection API',
    'api_version' => '1.2.1',
    'rate' => [
        'limit' => (int) env('GETIP_RATE_LIMIT', 60),
        'window_seconds' => (int) env('GETIP_RATE_WINDOW_SECONDS', 60),
    ],
];
