<?php

declare(strict_types=1);

namespace App\Features\GetIp\Http\Controllers;

use App\Features\GetIp\Services\ClientIpDetector;
use App\Features\GetIp\Services\GetIpResponseFactory;
use App\Features\GetIp\Support\GetIpQueryValidator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class GetIpController
{
    public function __construct(
        private readonly ClientIpDetector $ipDetector,
        private readonly GetIpQueryValidator $queryValidator,
        private readonly GetIpResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $query = $this->queryValidator->parse($request);

        if ($request->isMethod('OPTIONS')) {
            return $this->responseFactory->noContent();
        }

        if (! $request->isMethod('GET')) {
            return $this->responseFactory->error('Method Not Allowed. Use GET.', 405, $query->wantsJson());
        }

        if ($query->wantV4 && $query->wantV6) {
            return $this->responseFactory->error(
                'Use apenas ?ipv4 ou ?ipv6, não ambos simultaneamente.',
                400,
                $query->wantsJson()
            );
        }

        $clientIp = $this->ipDetector->detect($request);
        $version = $this->getIpVersion($clientIp);

        if ($query->wantV4 && $version !== 4) {
            return $this->responseFactory->error(
                "Nenhum IPv4 detectado. IP atual: {$clientIp} (v{$version}).",
                404,
                $query->wantsJson()
            );
        }

        if ($query->wantV6 && $version !== 6) {
            return $this->responseFactory->error(
                "Nenhum IPv6 detectado. IP atual: {$clientIp} (v{$version}).",
                404,
                $query->wantsJson()
            );
        }

        return $this->responseFactory->success($clientIp, $version, $query->wantsJson());
    }

    private function getIpVersion(string $ip): ?int
    {
        return match (true) {
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false => 4,
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false => 6,
            default => null,
        };
    }
}
