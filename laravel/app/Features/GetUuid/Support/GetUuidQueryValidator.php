<?php

declare(strict_types=1);

namespace App\Features\GetUuid\Support;

use Illuminate\Http\Request;

final class GetUuidQueryValidator
{
    public function parse(Request $request): GetUuidQueryData
    {
        $rawVersion = trim((string) $request->query('version', ''));

        if ($rawVersion === '') {
            return new GetUuidQueryData(version: null);
        }

        if (! ctype_digit($rawVersion)) {
            return new GetUuidQueryData(version: -1);
        }

        return new GetUuidQueryData(version: (int) $rawVersion);
    }
}
