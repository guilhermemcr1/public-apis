<?php

declare(strict_types=1);

namespace App\Features\GetIp\Support;

final readonly class GetIpQueryData
{
    public function __construct(
        public string $format,
        public bool $wantV4,
        public bool $wantV6,
    ) {
    }

    public function wantsJson(): bool
    {
        return $this->format === 'json';
    }
}
