<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\GetIp\Contracts\GeoIpLookupContract;
use Tests\TestCase;

final class GetIpGeoTest extends TestCase
{
    public function test_json_without_geo_includes_response_code_and_server_timezone(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/getip?format=json');

        $response->assertOk()
            ->assertJsonPath('response_code', 200)
            ->assertJsonStructure(['meta' => ['timestamp', 'server_timezone']])
            ->assertJsonMissingPath('geo');
    }

    public function test_geo_without_json_returns_400(): void
    {
        $response = $this->get('/getip?geo=1');

        $response->assertStatus(400)
            ->assertSee('geo', false)
            ->assertSee('json', false);
    }

    public function test_geo_json_minimal_includes_mocked_geo_payload(): void
    {
        $this->mock(GeoIpLookupContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with('203.0.113.10', 'minimal')
                ->andReturn([
                    'location' => [
                        'country' => [
                            'iso_code' => 'BR',
                            'name' => 'Brazil',
                        ],
                        'state' => [
                            'iso_code' => 'SP',
                            'name' => 'São Paulo',
                        ],
                        'city' => 'Campinas',
                        'postal_code' => '13000',
                        'timezone' => 'America/Sao_Paulo',
                    ],
                    'isp' => [
                        'asn' => 64_500,
                        'organization' => 'Example ISP',
                    ],
                    'warnings' => [],
                ]);
        });

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/getip?format=json&geo=1');

        $response->assertOk()
            ->assertJsonPath('response_code', 200)
            ->assertJsonPath('geo.location.country.iso_code', 'BR')
            ->assertJsonPath('geo.location.state.iso_code', 'SP')
            ->assertJsonPath('geo.isp.asn', 64_500)
            ->assertJsonPath('geo.isp.organization', 'Example ISP')
            ->assertJsonMissingPath('geo.privacy')
            ->assertJsonMissingPath('geo.location.continent')
            ->assertJsonMissingPath('meta.geo_warnings');
    }

    public function test_geo_full_calls_lookup_with_full_and_returns_extended_location(): void
    {
        $this->mock(GeoIpLookupContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with('203.0.113.11', 'full')
                ->andReturn([
                    'location' => [
                        'continent' => ['code' => 'NA', 'name' => 'North America'],
                        'country' => ['iso_code' => 'US', 'name' => 'United States', 'in_european_union' => false],
                        'subdivision' => ['iso_code' => 'CA', 'name' => 'California'],
                        'city' => 'Los Angeles',
                        'postal_code' => '90001',
                        'coordinates' => ['latitude' => 34.0, 'longitude' => -118.0, 'accuracy_radius_km' => 10],
                        'timezone' => 'America/Los_Angeles',
                    ],
                    'isp' => ['asn' => 15169, 'organization' => 'Google LLC'],
                    'warnings' => [],
                ]);
        });

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->get('/getip?format=json&geo=full');

        $response->assertOk()
            ->assertJsonPath('geo.location.continent.code', 'NA')
            ->assertJsonPath('geo.location.subdivision.iso_code', 'CA')
            ->assertJsonMissingPath('geo.location.state')
            ->assertJsonMissingPath('geo.privacy');

        self::assertEqualsWithDelta(
            34.0,
            (float) $response->json('geo.location.coordinates.latitude'),
            0.001,
        );
    }

    public function test_geo_json_private_ip_returns_null_geo_without_mock(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/getip?format=json&geo=1');

        $response->assertOk()
            ->assertJsonPath('geo.location', null)
            ->assertJsonPath('geo.isp', null)
            ->assertJsonMissingPath('geo.privacy')
            ->assertJsonPath('private', true);
    }

    public function test_geo_warnings_surface_in_meta_when_present(): void
    {
        $this->mock(GeoIpLookupContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with('203.0.113.55', 'minimal')
                ->andReturn([
                    'location' => null,
                    'isp' => null,
                    'warnings' => ['city_database_unavailable'],
                ]);
        });

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.55'])
            ->get('/getip?format=json&geo=1');

        $response->assertOk()
            ->assertJsonPath('meta.geo_warnings', ['city_database_unavailable']);
    }
}
