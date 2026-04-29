<?php

declare(strict_types=1);

namespace App\Features\GetUuid\Http\Controllers;

use App\Features\GetUuid\Services\GetUuidResponseFactory;
use App\Features\GetUuid\Services\UuidGeneratorService;
use App\Features\GetUuid\Support\GetUuidQueryValidator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class GetUuidController
{
    public function __construct(
        private readonly UuidGeneratorService $uuidGenerator,
        private readonly GetUuidQueryValidator $queryValidator,
        private readonly GetUuidResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->responseFactory->noContent();
        }

        if (! $request->isMethod('GET')) {
            return $this->responseFactory->error('Method Not Allowed. Use GET.', 405);
        }

        $query = $this->queryValidator->parse($request);
        $version = $query->version ?? 4;

        if (! in_array($version, [4, 7], true)) {
            return $this->responseFactory->error('Versão de UUID inválida. Use apenas 4 ou 7.', 400);
        }

        $uuid = $this->uuidGenerator->generate($version);

        return $this->responseFactory->success($uuid, $version);
    }
}
