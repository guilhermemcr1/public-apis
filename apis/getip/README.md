# API `getip`

Documentação da API pública de detecção de IP (**versão atual da API: 1.5.0** — campo `meta.api_version` nas respostas JSON).

**Contrato JSON:** todo retorno com `format=json` inclui **`response_code`** no objeto raiz (espelha o HTTP: `200`, `400`, etc.). Erros usam **`response_code`** — o campo antigo `status` no corpo foi **removido**. **`meta.timestamp`** segue ISO8601 com o fuso configurado na app Laravel (**`APP_TIMEZONE`**); **`meta.server_timezone`** indica o identificador IANA usado (ex.: `America/Sao_Paulo`).

Além do IP em texto ou JSON simples, você pode pedir **`geo`** (GeoLite2 City em modo **minimal** ou **full**, base **ASN** exposta como **`geo.isp`**, e **Anonymous IP** em **`geo.privacy`**) — ver parâmetros abaixo e **Estratégia operacional**.

## Base URL

- Produção: `https://api.galarca.dev`
- Local: `http://127.0.0.1:8000`

## Swagger

- UI (Get IP): `https://api.galarca.dev/api/documentation/getip`
- JSON OpenAPI (Get IP): `https://api.galarca.dev/docs/getip`

## Endpoint

### `GET /getip`

Retorna o IP público detectado do cliente (cabeçalhos como `CF-Connecting-IP`, `X-Forwarded-For`, etc., são considerados quando presentes).

#### Query params suportados

- `format=json`: retorna payload JSON (com `response_code`, `meta.timestamp`, `meta.server_timezone`).
- `geo`: **só com `format=json`**. Omitido = sem bloco `geo`. Valores **truthy**, flag **`?geo`** sem valor, ou `minimal` / `min`: **localização minimal** (país, estado, cidade, CEP, timezone) + **`geo.isp`** + **`geo.privacy`**. **`geo=full`**: mesma estrutura enriquecida de City (**continent**, **subdivision**, **coordinates**, EU em país, etc.) + **isp** + **privacy**. **`geo=false`** (ou `0` / `no` / `off`) desativa.
- `ipv4`: exige resposta IPv4
- `ipv6`: exige resposta IPv6

**Comportamento de `geo`:** sem bases `.mmdb`, IPs privados/reservados ou registro ausente na MaxMind, `geo.location` e/ou `geo.isp` podem vir `null`. **`geo.privacy`** é sempre incluído; sem base Anonymous IP ou com falha de lookup, todos os flags ficam `false` e pode haver `meta.geo_warnings` (ex.: `anonymous_ip_database_unavailable`). Aviso **`isp_database_unavailable`** substitui o antigo nome ASN quando a base ISP/ASN não está disponível.

**Semântica de `geo.privacy` (campos MaxMind agregados):**

| Campo API | Origem GeoLite2 Anonymous IP |
|-----------|-------------------------------|
| `is_vpn` | `is_anonymous_vpn` |
| `is_proxy` | `is_public_proxy` **ou** `is_residential_proxy` |
| `is_tor` | `is_tor_exit_node` |
| `is_hosting` | `is_hosting_provider` |

Mensagens operacionais opcionais: `meta.geo_warnings`. Deploy e atualização: secção **Estratégia operacional** abaixo.

#### Respostas esperadas

- `200`: sucesso
- `400`: parâmetros inválidos (ex.: `ipv4` e `ipv6` juntos; ou `geo` sem `format=json`)
- `404`: tipo de IP solicitado não encontrado
- `405`: método não permitido
- `429`: limite de requisições por IP atingido

## Estratégia operacional (implantação)

### Por que arquivo `.mmdb` em disco

As bases GeoLite2 são distribuídas como binários **MaxMind DB**. A API usa leitura local (biblioteca oficial PHP) por IP — **sem importar os dados para MySQL/PostgreSQL para cada request**, o que preserva latência e simplifica atualizações (substituir arquivos).

### Variáveis de ambiente (Laravel)

| Variável | Função |
|----------|--------|
| `MAXMIND_LICENSE_KEY` | Obrigatória para `php artisan geoip:update` baixar City + ASN. |
| `GEOIP_SCHEDULE_ENABLED` | `true` (padrão): agenda atualização semanal via Laravel Scheduler. `false`: apenas atualização manual. |
| `GEOIP_CITY_DATABASE_PATH` / `GEOIP_ASN_DATABASE_PATH` / `GEOIP_ANONYMOUS_IP_DATABASE_PATH` | Opcional; padrões em `storage/app/geoip/` (`GeoLite2-City.mmdb`, `GeoLite2-ASN.mmdb`, `GeoLite2-Anonymous-IP.mmdb`). |
| `GEOIP_CITY_EDITION_ID` / `GEOIP_ASN_EDITION_ID` / `GEOIP_ANONYMOUS_IP_EDITION_ID` | Opcional; padrões `GeoLite2-City`, `GeoLite2-ASN`, `GeoLite2-Anonymous-IP`. |

### Fluxo recomendado

1. **Bootstrap / primeiro deploy:** `php artisan geoip:update` na pasta `laravel/` (rede outbound HTTPS + `tar` disponível no servidor). O comando baixa **três** editions configuradas em `config/geoip.php` (City, ASN, Anonymous IP).
2. **Rotina:** Cron em produção executando `php artisan schedule:run` (ex.: a cada minuto). Com `GEOIP_SCHEDULE_ENABLED=true`, o comando `geoip:update` roda **semanalmente** (domingo 04:30, timezone da app).
3. **Manual:** `php artisan geoip:update --edition=GeoLite2-City` (ou `GeoLite2-ASN`) para atualizar só uma base.
4. **Pós-deploy:** `php artisan config:cache` após mudar `.env`.

### Checklist rápido

- [ ] Três `.mmdb` relevantes presentes em `storage/app/geoip/` (City, ASN, Anonymous IP), ou aceitar que `geo` omitirá dados conforme disponibilidade.
- [ ] `MAXMIND_LICENSE_KEY` definida em produção (nunca commitada).
- [ ] Cron com `schedule:run` ativo se quiser atualização automática.
- [ ] Espaço em disco monitorado (City é o arquivo maior).
- [ ] Atribuição GeoLite2 respeitada em produtos públicos que exibem os dados (ver fim deste README).

## Exemplos de resposta da API

### `GET /getip`

```txt
203.0.113.10
```

### `GET /getip?format=json`

```json
{
  "response_code": 200,
  "ip": "203.0.113.10",
  "version": "v4",
  "private": false,
  "meta": {
    "api": "IP Detection API",
    "api_version": "1.5.0",
    "timestamp": "2026-04-29T17:30:00-03:00",
    "server_timezone": "America/Sao_Paulo"
  }
}
```

### `GET /getip?format=json&geo=1` — localização **minimal** (ilustrativo)

Por padrão (`geo`, `geo=1`, `geo=minimal`, flag `?geo`): país, estado (`state`), cidade, CEP, timezone — sem continent nem coordenadas.

```json
{
  "response_code": 200,
  "ip": "203.0.113.10",
  "version": "v4",
  "private": false,
  "meta": {
    "api": "IP Detection API",
    "api_version": "1.5.0",
    "timestamp": "2026-05-01T09:00:00-03:00",
    "server_timezone": "America/Sao_Paulo"
  },
  "geo": {
    "location": {
      "country": { "iso_code": "US", "name": "United States" },
      "state": { "iso_code": "CA", "name": "California" },
      "city": "Los Angeles",
      "postal_code": "90001",
      "timezone": "America/Los_Angeles"
    },
    "isp": {
      "asn": 64500,
      "organization": "Example Telecom"
    },
    "privacy": {
      "is_vpn": false,
      "is_proxy": false,
      "is_tor": false,
      "is_hosting": false
    }
  }
}
```

### `GET /getip?format=json&geo=full` — localização **completa** (ilustrativo)

```json
{
  "response_code": 200,
  "ip": "203.0.113.10",
  "version": "v4",
  "private": false,
  "meta": {
    "api": "IP Detection API",
    "api_version": "1.5.0",
    "timestamp": "2026-05-01T09:00:00-03:00",
    "server_timezone": "America/Sao_Paulo"
  },
  "geo": {
    "location": {
      "continent": { "code": "NA", "name": "North America" },
      "country": { "iso_code": "US", "name": "United States", "in_european_union": false },
      "subdivision": { "iso_code": "CA", "name": "California" },
      "city": "Los Angeles",
      "postal_code": "90001",
      "coordinates": {
        "latitude": 34.0544,
        "longitude": -118.244,
        "accuracy_radius_km": 10
      },
      "timezone": "America/Los_Angeles"
    },
    "isp": {
      "asn": 64500,
      "organization": "Example Telecom"
    },
    "privacy": {
      "is_vpn": false,
      "is_proxy": false,
      "is_tor": false,
      "is_hosting": false
    }
  }
}
```

### `GET /getip?format=json&ipv4&ipv6` (erro esperado)

```json
{
  "response_code": 400,
  "error": "Use apenas ?ipv4 ou ?ipv6, não ambos simultaneamente."
}
```

### `POST /getip?format=json` (erro esperado)

```json
{
  "response_code": 405,
  "error": "Method Not Allowed. Use GET."
}
```

## Exemplos de uso

Use `...?format=json` para JSON simples (`response_code`, `meta`), ou `...?format=json&geo=1` para **minimal**, ou `...&geo=full` para City completa (bases devem estar instaladas no servidor).

### JavaScript (browser)

```js
const url = 'https://api.galarca.dev/getip?format=json';
// const url = 'https://api.galarca.dev/getip?format=json&geo=1';
// const url = 'https://api.galarca.dev/getip?format=json&geo=full';

async function getPublicIp() {
  const response = await fetch(url, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Falha ao consultar IP: ${response.status}`);
  }

  const data = await response.json();
  console.log('response_code:', data.response_code);
  console.log('IP:', data.ip);
  console.log('Versão:', data.version);
  if (data.geo) {
    console.log('Geo:', data.geo);
  }
}

getPublicIp().catch(console.error);
```

### PHP (cURL)

```php
<?php

$url = 'https://api.galarca.dev/getip?format=json';
// $url = 'https://api.galarca.dev/getip?format=json&geo=1';
// $url = 'https://api.galarca.dev/getip?format=json&geo=full';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
]);

$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($result === false) {
    throw new RuntimeException('Erro cURL: ' . curl_error($ch));
}

curl_close($ch);

if ($status !== 200) {
    throw new RuntimeException("Erro HTTP: {$status}");
}

$data = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

echo "response_code: {$data['response_code']}" . PHP_EOL;
echo "IP: {$data['ip']}" . PHP_EOL;
echo "Versão: {$data['version']}" . PHP_EOL;
if (isset($data['geo'])) {
    echo 'Geo: ' . json_encode($data['geo'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}
```

### Node.js (nativo com fetch)

```js
const url = 'https://api.galarca.dev/getip?format=json';
// const url = 'https://api.galarca.dev/getip?format=json&geo=1';
// const url = 'https://api.galarca.dev/getip?format=json&geo=full';

async function run() {
  const response = await fetch(url, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  const data = await response.json();
  console.log(data);
}

run().catch((error) => {
  console.error('Erro ao consultar API:', error.message);
  process.exit(1);
});
```

## Testes rápidos com curl

```bash
curl "https://api.galarca.dev/getip"
curl "https://api.galarca.dev/getip?format=json"
curl "https://api.galarca.dev/getip?format=json&ipv4"
curl "https://api.galarca.dev/getip?format=json&ipv6"
curl "https://api.galarca.dev/getip?format=json&geo=1"
curl "https://api.galarca.dev/getip?format=json&geo=full"
```

## Atribuição (GeoLite2)

Este produto inclui dados GeoLite2 criados pela MaxMind, disponíveis em [https://www.maxmind.com](https://www.maxmind.com).

