<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Galarca Public APIs',
    version: '1.0.0',
    description: 'Documentação oficial das APIs públicas da plataforma Galarca'
)]
#[OA\Server(
    url: 'https://api.galarca.dev',
    description: 'Servidor de Produção'
)]
#[OA\Get(
    path: '/getip',
    summary: 'Retorna o IP público do cliente',
    parameters: [
        new OA\QueryParameter(name: 'format', description: 'Use json para resposta estruturada', required: false, schema: new OA\Schema(type: 'string', enum: ['json'])),
        new OA\QueryParameter(name: 'ipv4', description: 'Filtra apenas IPv4', required: false, schema: new OA\Schema(type: 'boolean')),
        new OA\QueryParameter(name: 'ipv6', description: 'Filtra apenas IPv6', required: false, schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Resposta em texto ou JSON'
        ),
        new OA\Response(response: 400, description: 'Parâmetros inválidos'),
        new OA\Response(response: 404, description: 'Tipo de IP solicitado não encontrado'),
        new OA\Response(response: 429, description: 'Rate limit atingido'),
    ]
)]
#[OA\Get(
    path: '/getuuid',
    summary: 'Gera UUID válido',
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
class OpenApiSpec
{
    // Arquivo usado apenas para annotations globais
}