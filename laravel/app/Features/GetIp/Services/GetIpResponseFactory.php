<?php

declare(strict_types=1);

namespace App\Features\GetIp\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class GetIpResponseFactory
{
    /**
     * @param  array<string, mixed>  $geoMeta  merged into meta (ex.: geo_warnings)
     * @param  array<string, string|int>  $extraHeaders
     */
    public function success(
        string $ip,
        ?int $version,
        bool $json,
        array $extraHeaders = [],
        ?array $geo = null,
        array $geoMeta = [],
    ): Response|JsonResponse {
        if ($json) {
            $tz = (string) config('app.timezone');
            $meta = [
                'api' => (string) config('getip.api_name'),
                'api_version' => (string) config('getip.api_version'),
                'timestamp' => now($tz)->toIso8601String(),
                'server_timezone' => $tz,
            ];
            if ($geoMeta !== []) {
                $meta = array_merge($meta, $geoMeta);
            }

            $payload = [
                'response_code' => 200,
                'ip' => $ip,
                'version' => 'v'.$version,
                'private' => $this->isPrivateIp($ip),
                'meta' => $meta,
            ];
            if ($geo !== null) {
                $payload['geo'] = $geo;
            }

            return $this->json(
                $payload,
                200,
                $extraHeaders
            );
        }

        return $this->text($ip, 200, $extraHeaders);
    }

    /**
     * @param  array<string, string|int>  $extraHeaders
     */
    public function error(string $message, int $status, bool $json, array $extraHeaders = []): Response|JsonResponse
    {
        if ($json) {
            return $this->json(
                [
                    'response_code' => $status,
                    'error' => $message,
                ],
                $status,
                $extraHeaders
            );
        }

        return $this->text("Error {$status}: {$message}", $status, $extraHeaders);
    }

    /**
     * @param  array<string, string|int>  $extraHeaders
     */
    public function noContent(array $extraHeaders = []): Response
    {
        return $this->withBaseHeaders(response('', 204), $extraHeaders);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|int>  $extraHeaders
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
     * @param  array<string, string|int>  $extraHeaders
     */
    private function text(string $content, int $status, array $extraHeaders = []): Response
    {
        $response = response($content, $status);
        $response->header('Content-Type', 'text/plain; charset=utf-8');

        return $this->withBaseHeaders($response, $extraHeaders);
    }

    /**
     * @template T of Response|JsonResponse
     *
     * @param  T  $response
     * @param  array<string, string|int>  $extraHeaders
     * @return T
     */
    private function withBaseHeaders(Response|JsonResponse $response, array $extraHeaders): Response|JsonResponse
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Robots-Tag', 'noindex');

        foreach ($extraHeaders as $name => $value) {
            $response->header($name, (string) $value);
        }

        return $response;
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
