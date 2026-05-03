<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpIsp',
    nullable: true,
    properties: [
        new OA\Property(property: 'asn', type: 'integer', nullable: true),
        new OA\Property(property: 'organization', type: 'string', nullable: true),
    ],
    type: 'object'
)]
final class SchemaGetIpIsp {}
