<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpGeoBlock',
    properties: [
        new OA\Property(
            property: 'location',
            type: 'object',
            nullable: true,
            additionalProperties: true,
            description: 'minimal: country, state, city, postal_code, timezone; full: inclui continent_code, subdivisions, latitude/longitude…'
        ),
        new OA\Property(property: 'isp', ref: '#/components/schemas/GetIpIsp', nullable: true),
        new OA\Property(property: 'privacy', ref: '#/components/schemas/GetIpPrivacy'),
    ],
    type: 'object'
)]
final class SchemaGetIpGeoBlock {}
