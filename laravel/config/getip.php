<?php

/*
| Geo opcional (GeoLite2 City + ASN): variáveis em .env.example / config/geoip.php — ver apis/getip/README.md
*/

return [
    'api_name' => 'IP Detection API',
    'api_version' => '1.6.0',
    'rate' => [
        'limit' => (int) env('GETIP_RATE_LIMIT', 60),
        'window_seconds' => (int) env('GETIP_RATE_WINDOW_SECONDS', 60),
    ],
];
