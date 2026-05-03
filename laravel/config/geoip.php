<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Agendamento da atualização das bases (.mmdb)
    |--------------------------------------------------------------------------
    */
    'schedule_enabled' => (bool) env('GEOIP_SCHEDULE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Credenciais MaxMind (GeoLite download)
    |--------------------------------------------------------------------------
    */
    'license_key' => env('MAXMIND_LICENSE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Bases GeoLite2 (edition_id oficial na MaxMind + caminho local do .mmdb)
    |--------------------------------------------------------------------------
    */
    'databases' => [
        'city' => [
            'edition_id' => env('GEOIP_CITY_EDITION_ID', 'GeoLite2-City'),
            'path' => env('GEOIP_CITY_DATABASE_PATH', storage_path('app/geoip/GeoLite2-City.mmdb')),
        ],
        'asn' => [
            'edition_id' => env('GEOIP_ASN_EDITION_ID', 'GeoLite2-ASN'),
            'path' => env('GEOIP_ASN_DATABASE_PATH', storage_path('app/geoip/GeoLite2-ASN.mmdb')),
        ],
        'anonymous_ip' => [
            'edition_id' => env('GEOIP_ANONYMOUS_IP_EDITION_ID', 'GeoLite2-Anonymous-IP'),
            'path' => env('GEOIP_ANONYMOUS_IP_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Anonymous-IP.mmdb')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale para nomes legíveis (país, cidade, etc.)
    |--------------------------------------------------------------------------
    */
    'locales' => ['pt-BR', 'en'],
];
