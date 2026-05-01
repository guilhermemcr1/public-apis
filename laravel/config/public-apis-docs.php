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
            'description' => 'Detecta o endereço IP do cliente, com suporte a texto ou JSON e filtros ipv4/ipv6.',
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
