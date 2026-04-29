<?php

declare(strict_types=1);

namespace App\Features\GetIp\Services;

use Illuminate\Http\Request;

final class ClientIpDetector
{
    /**
     * @var array<int, string>
     */
    private array $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    public function detect(Request $request): string
    {
        foreach ($this->headers as $header) {
            $value = (string) $request->server($header, '');
            $ip = $this->extractIp($value);

            if ($ip !== null) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    private function extractIp(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $ip = trim(explode(',', $value)[0]);

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}
