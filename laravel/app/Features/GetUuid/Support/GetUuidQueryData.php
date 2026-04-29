<?php

declare(strict_types=1);

namespace App\Features\GetUuid\Support;

final readonly class GetUuidQueryData
{
    public function __construct(
        public ?int $version,
    ) {
    }
}
