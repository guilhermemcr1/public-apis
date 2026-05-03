<?php

declare(strict_types=1);

namespace App\Features\GetIp\Contracts;

interface GeoIpLookupContract
{
    /**
     * @param  'minimal'|'full'  $locationDetail
     * @return array{
     *     location: array<string, mixed>|null,
     *     isp: array<string, mixed>|null,
     *     warnings: list<string>
     * }
     */
    public function lookup(string $ipAddress, string $locationDetail): array;
}
