<?php

namespace App\OpenApi\GetIp;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'APIs Públicas - Get IP',
    version: '1.6.0',
    description: 'Detecção do IP público. JSON inclui response_code e meta.server_timezone. geo (format=json): minimal (padrão) ou geo=full para City completa; geo.isp (GeoLite2 ASN). Operação: apis/getip/README.md.'
)]
#[OA\Tag(name: 'Get IP', description: 'IP público; geo GeoLite2 (minimal/full); isp (ASN + organização).')]
#[OA\Server(
    url: 'https://api.galarca.dev',
    description: 'Servidor de Produção'
)]
#[OA\Get(
    path: '/getip',
    summary: 'Retorna o IP público do cliente (JSON com response_code; geo minimal ou full)',
    tags: ['Get IP'],
    parameters: [
        new OA\QueryParameter(name: 'format', description: 'Use json para resposta estruturada', required: false, schema: new OA\Schema(type: 'string', enum: ['json'])),
        new OA\QueryParameter(name: 'ipv4', description: 'Filtra apenas IPv4', required: false, schema: new OA\Schema(type: 'boolean')),
        new OA\QueryParameter(name: 'ipv6', description: 'Filtra apenas IPv6', required: false, schema: new OA\Schema(type: 'boolean')),
        new OA\QueryParameter(
            name: 'geo',
            description: 'Sem geo: omita o parâmetro. Truthy/flag/minimal: país, estado, cidade, CEP, timezone + isp. full: City completa (continent, coordinates, subdivision…). Exige format=json.',
            required: false,
            schema: new OA\Schema(type: 'string', nullable: true)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Sucesso. Com format=json: schema GetIpJsonSuccess. Sem format=json: apenas o IP em text/plain.',
            content: [
                new OA\JsonContent(ref: '#/components/schemas/GetIpJsonSuccess'),
                new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string', example: '203.0.113.42')
                ),
            ]
        ),
        new OA\Response(
            response: 400,
            description: 'Parâmetros inválidos. Com format=json: response_code + error (sem campo status). Sem JSON: texto Error N: mensagem.',
            content: [
                new OA\JsonContent(ref: '#/components/schemas/GetIpJsonError'),
                new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string', example: 'Error 400: O parâmetro geo exige format=json.')
                ),
            ]
        ),
        new OA\Response(
            response: 404,
            description: 'IPv4 ou IPv6 solicitado não disponível para o cliente atual.',
            content: [
                new OA\JsonContent(ref: '#/components/schemas/GetIpJsonError'),
                new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string')
                ),
            ]
        ),
        new OA\Response(
            response: 405,
            description: 'Apenas GET (e OPTIONS para CORS).',
            content: [
                new OA\JsonContent(ref: '#/components/schemas/GetIpJsonError'),
                new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string')
                ),
            ]
        ),
        new OA\Response(
            response: 429,
            description: 'Rate limit atingido.',
            content: [
                new OA\JsonContent(ref: '#/components/schemas/GetIpJsonError'),
                new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string')
                ),
            ]
        ),
    ]
)]
final class OpenApiSpec {}
