<?php

namespace App\OpenApi\GetUuid;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Galarca Public APIs - Get UUID',
    version: '1.0.0',
    description: 'Documentação oficial da API pública de geração de UUID'
)]
#[OA\Server(
    url: 'https://api.galarca.dev',
    description: 'Servidor de Produção'
)]
#[OA\Get(
    path: '/getuuid',
    summary: 'Gera UUID válido',
    tags: ['Get UUID'],
    parameters: [
        new OA\QueryParameter(
            name: 'version',
            description: 'Versão do UUID (4 ou 7)',
            required: false,
            schema: new OA\Schema(type: 'integer', enum: [4, 7])
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'UUID gerado com sucesso',
            content: new OA\JsonContent(
                required: ['uuid', 'version'],
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'version', type: 'integer', enum: [4, 7]),
                ]
            )
        ),
        new OA\Response(response: 400, description: 'Versão inválida'),
        new OA\Response(response: 429, description: 'Rate limit atingido'),
    ]
)]
final class OpenApiSpec
{
}
