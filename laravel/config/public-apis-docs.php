<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogo das APIs públicas (hub em /docs e /api/documentation)
    |--------------------------------------------------------------------------
    | Os paths de Swagger/OpenAPI vêm de config/l5-swagger.php por slug.
    */
    'apis' => [
        [
            'slug' => 'getip',
            'name' => 'Get IP',
            'description' => 'JSON com response_code e geo opcional GeoLite2 (minimal ou geo=full: City + isp). format=json.',
            'endpoint' => '/getip',
        ],
        [
            'slug' => 'getuuid',
            'name' => 'Get UUID',
            'description' => 'Gera UUID nas versões 4 ou 7, com fallback para v4 quando a versão não é informada.',
            'endpoint' => '/getuuid',
        ],
    ],
];
