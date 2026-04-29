<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class GetUuidApiTest extends TestCase
{
    public function test_it_generates_uuid_v4_when_version_is_4(): void
    {
        $response = $this->getJson('/getuuid?version=4');

        $response->assertOk()
            ->assertJsonPath('version', 4);

        $uuid = (string) $response->json('uuid');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function test_it_generates_uuid_v7_when_version_is_7(): void
    {
        $response = $this->getJson('/getuuid?version=7');

        $response->assertOk()
            ->assertJsonPath('version', 7);

        $uuid = (string) $response->json('uuid');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function test_it_falls_back_to_v4_when_version_is_missing(): void
    {
        $response = $this->getJson('/getuuid');

        $response->assertOk()
            ->assertJsonPath('version', 4);

        $uuid = (string) $response->json('uuid');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function test_it_returns_bad_request_for_invalid_version(): void
    {
        $response = $this->getJson('/getuuid?version=9');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Versão de UUID inválida. Use apenas 4 ou 7.',
                'status' => 400,
            ]);
    }

    public function test_it_rejects_non_get_methods(): void
    {
        $response = $this->postJson('/getuuid');

        $response->assertStatus(405)
            ->assertJson([
                'error' => 'Method Not Allowed. Use GET.',
                'status' => 405,
            ]);
    }

    public function test_it_returns_security_headers(): void
    {
        $response = $this->getJson('/getuuid');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Robots-Tag', 'noindex');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'");

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }
}
