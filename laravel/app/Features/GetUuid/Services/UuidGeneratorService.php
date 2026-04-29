<?php

declare(strict_types=1);

namespace App\Features\GetUuid\Services;

use Illuminate\Support\Str;

final class UuidGeneratorService
{
    public function generate(int $version): string
    {
        return match ($version) {
            4 => (string) Str::uuid(),
            7 => (string) Str::uuid7(),
            default => throw new \InvalidArgumentException('Versão de UUID inválida. Use apenas 4 ou 7.'),
        };
    }
}
