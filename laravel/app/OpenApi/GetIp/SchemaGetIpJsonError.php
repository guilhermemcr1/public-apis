<?php

declare(strict_types=1);

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetIpJsonError',
    required: ['response_code', 'error'],
    properties: [
        new OA\Property(property: 'response_code', type: 'integer', example: 400),
        new OA\Property(property: 'error', type: 'string'),
    ],
    type: 'object'
)]
final class SchemaGetIpJsonError {}
