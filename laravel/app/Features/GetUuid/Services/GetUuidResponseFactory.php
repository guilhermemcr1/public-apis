<?php

declare(strict_types=1);

namespace App\Features\GetUuid\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class GetUuidResponseFactory
{
    public function success(string $uuid, int $version, array $extraHeaders = []): JsonResponse
    {
        return $this->json(
            [
                'uuid' => $uuid,
                'version' => $version,
            ],
            200,
            $extraHeaders
        );
    }

    public function error(string $message, int $status, array $extraHeaders = []): JsonResponse
    {
        return $this->json(
            [
                'error' => $message,
                'status' => $status,
            ],
            $status,
            $extraHeaders
        );
    }

    public function noContent(array $extraHeaders = []): Response
    {
        return $this->withBaseHeaders(response('', 204), $extraHeaders);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string|int> $extraHeaders
     */
    private function json(array $payload, int $status, array $extraHeaders = []): JsonResponse
    {
        $response = response()->json(
            $payload,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        return $this->withBaseHeaders($response, $extraHeaders);
    }

    /**
     * @template T of Response|JsonResponse
     *
     * @param T $response
     * @param array<string, string|int> $extraHeaders
     *
     * @return T
     */
    private function withBaseHeaders(Response|JsonResponse $response, array $extraHeaders): Response|JsonResponse
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Robots-Tag', 'noindex');
        $response->header('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'");

        foreach ($extraHeaders as $name => $value) {
            $response->header($name, (string) $value);
        }

        return $response;
    }
}
