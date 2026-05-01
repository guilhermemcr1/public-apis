<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DocsHubTest extends TestCase
{
    public function test_docs_returns_hub_page(): void
    {
        $response = $this->get('/docs');

        $response->assertOk()
            ->assertSee('Documentação das APIs Públicas', false)
            ->assertSee('Get IP', false)
            ->assertSee('Get UUID', false)
            ->assertSee('/api/documentation/getip', false)
            ->assertSee('/api/documentation/getuuid', false)
            ->assertSee('/docs/getip', false)
            ->assertSee('/docs/getuuid', false);
    }

    public function test_api_documentation_returns_same_hub(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertOk()
            ->assertSee('Documentação das APIs Públicas', false)
            ->assertSee('Get IP', false)
            ->assertSee('Get UUID', false);
    }
}
