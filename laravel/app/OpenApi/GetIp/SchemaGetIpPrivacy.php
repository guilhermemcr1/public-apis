<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpPrivacy',
    properties: [
        new OA\Property(property: 'is_vpn', type: 'boolean'),
        new OA\Property(property: 'is_proxy', type: 'boolean'),
        new OA\Property(property: 'is_tor', type: 'boolean'),
        new OA\Property(property: 'is_hosting', type: 'boolean'),
    ],
    type: 'object'
)]
final class SchemaGetIpPrivacy {}
