<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpJsonSuccess',
    required: ['response_code', 'ip', 'version', 'private', 'meta'],
    properties: [
        new OA\Property(property: 'response_code', type: 'integer', example: 200),
        new OA\Property(property: 'ip', type: 'string', example: '203.0.113.42'),
        new OA\Property(property: 'version', type: 'string', enum: ['v4', 'v6']),
        new OA\Property(property: 'private', type: 'boolean'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/GetIpMeta'),
        new OA\Property(property: 'geo', ref: '#/components/schemas/GetIpGeoBlock', nullable: true),
    ],
    type: 'object'
)]
final class SchemaGetIpJsonSuccess {}
