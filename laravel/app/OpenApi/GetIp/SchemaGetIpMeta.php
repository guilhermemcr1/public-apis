<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpMeta',
    properties: [
        new OA\Property(property: 'api', type: 'string', example: 'getip'),
        new OA\Property(property: 'api_version', type: 'string', example: '1.5.0'),
        new OA\Property(property: 'timestamp', type: 'string', description: 'ISO 8601 com offset do servidor (APP_TIMEZONE)'),
        new OA\Property(property: 'server_timezone', type: 'string', example: 'America/Sao_Paulo'),
        new OA\Property(
            property: 'geo_warnings',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'isp_database_unavailable'),
            nullable: true
        ),
    ],
    type: 'object'
)]
final class SchemaGetIpMeta {}
