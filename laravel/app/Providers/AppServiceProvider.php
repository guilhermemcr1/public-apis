<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('getip', function (Request $request): Limit {
            $maxAttempts = (int) config('getip.rate.limit', 60);
            $windowSeconds = (int) config('getip.rate.window_seconds', 60);
            $isJson = strtolower(trim((string) $request->query('format', ''))) === 'json';

            return Limit::perMinutes(max(1, (int) ceil($windowSeconds / 60)), $maxAttempts)
                ->by($request->ip() ?: (string) $request->server('REMOTE_ADDR', '0.0.0.0'))
                ->response(function (Request $request, array $headers) use ($isJson) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? config('getip.rate.window_seconds', 60));
                    $message = "Rate limit atingido. Tente novamente em {$retryAfter} segundos.";

                    if ($isJson) {
                        return response()->json(
                            [
                                'error' => $message,
                                'status' => 429,
                            ],
                            429,
                            $this->baseHeaders($headers + ['Retry-After' => $retryAfter]),
                            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                        );
                    }

                    return response(
                        "Error 429: {$message}",
                        429,
                        $this->baseHeaders($headers + ['Retry-After' => $retryAfter] + [
                            'Content-Type' => 'text/plain; charset=utf-8',
                        ])
                    );
                });
        });
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, string>
     */
    private function baseHeaders(array $headers = []): array
    {
        return array_merge([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ], array_map(static fn ($value) => (string) $value, $headers));
    }
}
