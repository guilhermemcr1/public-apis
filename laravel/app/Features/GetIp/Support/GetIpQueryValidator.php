<?php

declare(strict_types=1);

namespace App\Features\GetIp\Support;

use Illuminate\Http\Request;

final class GetIpQueryValidator
{
    public function parse(Request $request): GetIpQueryData
    {
        $format = strtolower(trim((string) $request->query('format', '')));
        $wantV4 = $request->query->has('ipv4');
        $wantV6 = $request->query->has('ipv6');

        return new GetIpQueryData(
            format: $format,
            wantV4: $wantV4,
            wantV6: $wantV6,
        );
    }
}
