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
            geoMode: $this->parseGeoMode($request),
        );
    }

    /**
     * @return 'off'|'minimal'|'full'
     */
    private function parseGeoMode(Request $request): string
    {
        if (! $request->query->has('geo')) {
            return 'off';
        }

        $raw = $request->query('geo');
        if ($raw === null || $raw === '') {
            return 'minimal';
        }

        $val = strtolower(trim((string) $raw));
        if ($val === 'full') {
            return 'full';
        }
        if (in_array($val, ['false', '0', 'no', 'off'], true)) {
            return 'off';
        }
        if (in_array($val, ['minimal', 'min', '1', 'true', 'yes', 'on'], true)) {
            return 'minimal';
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? 'minimal' : 'off';
    }
}
