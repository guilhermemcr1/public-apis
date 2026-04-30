<?php

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'APIs Públicas - Get IP',
    version: '1.0.0',
    description: 'Documentação oficial da API pública de detecção de IP'
)]
#[OA\Server(
    url: 'https://api.galarca.dev',
    description: 'Servidor de Produção'
)]
#[OA\Get(
    path: '/getip',
    summary: 'Retorna o IP público do cliente',
    tags: ['Get IP'],
    parameters: [
        new OA\QueryParameter(name: 'format', description: 'Use json para resposta estruturada', required: false, schema: new OA\Schema(type: 'string', enum: ['json'])),
        new OA\QueryParameter(name: 'ipv4', description: 'Filtra apenas IPv4', required: false, schema: new OA\Schema(type: 'boolean')),
        new OA\QueryParameter(name: 'ipv6', description: 'Filtra apenas IPv6', required: false, schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Resposta em texto ou JSON'),
        new OA\Response(response: 400, description: 'Parâmetros inválidos'),
        new OA\Response(response: 404, description: 'Tipo de IP solicitado não encontrado'),
        new OA\Response(response: 429, description: 'Rate limit atingido'),
    ]
)]
final class OpenApiSpec
{
}
