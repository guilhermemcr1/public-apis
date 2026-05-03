<?php

declare(strict_types=1);

namespace App\Features\GetIp\Services;

use App\Features\GetIp\Contracts\GeoIpLookupContract;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use Illuminate\Support\Facades\Log;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Throwable;

final class MaxMindGeoLiteLookup implements GeoIpLookupContract
{
    private ?Reader $cityReader = null;

    private bool $cityReaderFailed = false;

    private ?Reader $asnReader = null;

    private bool $asnReaderFailed = false;

    public function lookup(string $ipAddress, string $locationDetail): array
    {
        $warnings = [];

        if (! $this->isPublicIp($ipAddress)) {
            return [
                'location' => null,
                'isp' => null,
                'warnings' => [],
            ];
        }

        return [
            'location' => $this->lookupCity($ipAddress, $locationDetail, $warnings),
            'isp' => $this->lookupIsp($ipAddress, $warnings),
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  'minimal'|'full'  $locationDetail
     * @param  list<string>  $warnings
     */
    private function lookupCity(string $ipAddress, string $locationDetail, array &$warnings): ?array
    {
        $reader = $this->cityReader();
        if ($reader === null) {
            $warnings[] = 'city_database_unavailable';

            return null;
        }

        try {
            $city = $reader->city($ipAddress);

            return $locationDetail === 'full'
                ? $this->normalizeCityFull($city)
                : $this->normalizeCityMinimal($city);
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable $e) {
            Log::warning('GeoLite City lookup falhou.', [
                'ip' => $ipAddress,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $warnings[] = 'city_lookup_failed';

            return null;
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    private function lookupIsp(string $ipAddress, array &$warnings): ?array
    {
        $reader = $this->asnReader();
        if ($reader === null) {
            $warnings[] = 'isp_database_unavailable';

            return null;
        }

        try {
            $record = $reader->asn($ipAddress);

            if ($record->autonomousSystemNumber === null && $record->autonomousSystemOrganization === null) {
                return null;
            }

            return [
                'asn' => $record->autonomousSystemNumber,
                'organization' => $record->autonomousSystemOrganization,
            ];
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable $e) {
            Log::warning('GeoLite ISP/ASN lookup falhou.', [
                'ip' => $ipAddress,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $warnings[] = 'isp_lookup_failed';

            return null;
        }
    }

    /**
     * País, estado, cidade, CEP, timezone (sem continent/coordenadas).
     *
     * @return array<string, mixed>
     */
    private function normalizeCityMinimal(City $city): array
    {
        $country = $city->country;
        $sub = $city->mostSpecificSubdivision;

        $countryName = $country->name;
        $countryBlock = [
            'iso_code' => $country->isoCode,
            'name' => ($countryName !== null && $countryName !== '') ? $countryName : null,
        ];

        $subName = $sub->name;
        $stateBlock = null;
        if ($sub->isoCode !== null || ($subName !== null && $subName !== '')) {
            $stateBlock = [
                'iso_code' => $sub->isoCode,
                'name' => ($subName !== null && $subName !== '') ? $subName : null,
            ];
        }

        $cityName = $city->city->name;
        $cityNameOut = ($cityName !== null && $cityName !== '') ? $cityName : null;

        $postalCode = $city->postal->code;
        $postalOut = ($postalCode !== null && $postalCode !== '') ? $postalCode : null;

        return [
            'country' => $countryBlock,
            'state' => $stateBlock,
            'city' => $cityNameOut,
            'postal_code' => $postalOut,
            'timezone' => $city->location->timeZone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCityFull(City $city): array
    {
        $country = $city->country;
        $sub = $city->mostSpecificSubdivision;
        $continent = $city->continent;

        $continentName = $continent->name;
        $continentBlock = null;
        if ($continent->code !== null || ($continentName !== null && $continentName !== '')) {
            $continentBlock = [
                'code' => $continent->code,
                'name' => ($continentName !== null && $continentName !== '') ? $continentName : null,
            ];
        }

        $countryName = $country->name;
        $countryBlock = [
            'iso_code' => $country->isoCode,
            'name' => ($countryName !== null && $countryName !== '') ? $countryName : null,
            'in_european_union' => $country->isInEuropeanUnion,
        ];

        $subName = $sub->name;
        $subdivisionBlock = null;
        if ($sub->isoCode !== null || ($subName !== null && $subName !== '')) {
            $subdivisionBlock = [
                'iso_code' => $sub->isoCode,
                'name' => ($subName !== null && $subName !== '') ? $subName : null,
            ];
        }

        $cityName = $city->city->name;
        $cityNameOut = ($cityName !== null && $cityName !== '') ? $cityName : null;

        $postalCode = $city->postal->code;
        $postalOut = ($postalCode !== null && $postalCode !== '') ? $postalCode : null;

        $coordinates = [
            'latitude' => $city->location->latitude,
            'longitude' => $city->location->longitude,
            'accuracy_radius_km' => $city->location->accuracyRadius,
        ];

        return [
            'continent' => $continentBlock,
            'country' => $countryBlock,
            'subdivision' => $subdivisionBlock,
            'city' => $cityNameOut,
            'postal_code' => $postalOut,
            'coordinates' => $coordinates,
            'timezone' => $city->location->timeZone,
        ];
    }

    private function cityReader(): ?Reader
    {
        if ($this->cityReaderFailed) {
            return null;
        }

        if ($this->cityReader instanceof Reader) {
            return $this->cityReader;
        }

        $path = (string) config('geoip.databases.city.path');
        if ($path === '' || ! is_readable($path)) {
            $this->cityReaderFailed = true;

            return null;
        }

        try {
            /** @var list<string> $locales */
            $locales = config('geoip.locales', ['en']);

            $this->cityReader = new Reader($path, $locales);

            return $this->cityReader;
        } catch (InvalidDatabaseException $e) {
            Log::warning('GeoLite City .mmdb inválido ou corrompido.', ['path' => $path, 'message' => $e->getMessage()]);
            $this->cityReaderFailed = true;

            return null;
        } catch (Throwable $e) {
            Log::warning('GeoLite City reader não pôde ser aberto.', ['path' => $path, 'message' => $e->getMessage()]);
            $this->cityReaderFailed = true;

            return null;
        }
    }

    private function asnReader(): ?Reader
    {
        if ($this->asnReaderFailed) {
            return null;
        }

        if ($this->asnReader instanceof Reader) {
            return $this->asnReader;
        }

        $path = (string) config('geoip.databases.asn.path');
        if ($path === '' || ! is_readable($path)) {
            $this->asnReaderFailed = true;

            return null;
        }

        try {
            $this->asnReader = new Reader($path, ['en']);

            return $this->asnReader;
        } catch (InvalidDatabaseException $e) {
            Log::warning('GeoLite ASN .mmdb inválido ou corrompido.', ['path' => $path, 'message' => $e->getMessage()]);
            $this->asnReaderFailed = true;

            return null;
        } catch (Throwable $e) {
            Log::warning('GeoLite ASN reader não pôde ser aberto.', ['path' => $path, 'message' => $e->getMessage()]);
            $this->asnReaderFailed = true;

            return null;
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
