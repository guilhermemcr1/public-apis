# API `getip`

Documentação da API pública de detecção de IP (**versão atual da API: 1.6.0** — campo `meta.api_version` nas respostas JSON).

**Contrato JSON:** todo retorno com `format=json` inclui **`response_code`** no objeto raiz (espelha o HTTP: `200`, `400`, etc.). Erros usam **`response_code`** — o campo antigo `status` no corpo foi **removido**. **`meta.timestamp`** segue ISO8601 com o fuso configurado na app Laravel (**`APP_TIMEZONE`**); **`meta.server_timezone`** indica o identificador IANA usado (ex.: `America/Sao_Paulo`).

Além do IP em texto ou JSON simples, você pode pedir **`geo`** (GeoLite2 City em modo **minimal** ou **full**, base **ASN** exposta como **`geo.isp`**) — ver parâmetros abaixo e **Estratégia operacional**.

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
- `geo`: **só com `format=json`**. Omitido = sem bloco `geo`. Valores **truthy**, flag **`?geo`** sem valor, ou `minimal` / `min`: **localização minimal** (país, estado, cidade, CEP, timezone) + **`geo.isp`**. **`geo=full`**: mesma estrutura enriquecida de City (**continent**, **subdivision**, **coordinates**, EU em país, etc.) + **isp**. **`geo=false`** (ou `0` / `no` / `off`) desativa.
- `ipv4`: exige resposta IPv4
- `ipv6`: exige resposta IPv6

**Comportamento de `geo`:** sem bases `.mmdb`, IPs privados/reservados ou registro ausente na MaxMind, `geo.location` e/ou `geo.isp` podem vir `null`. Avisos opcionais em **`meta.geo_warnings`** (ex.: `city_database_unavailable`, **`isp_database_unavailable`** quando a base ASN não está disponível, `city_lookup_failed`, `isp_lookup_failed`).

Deploy e atualização: secção **Estratégia operacional** abaixo.

#### Respostas esperadas

- `200`: sucesso
- `400`: parâmetros inválidos (ex.: `ipv4` e `ipv6` juntos; ou `geo` sem `format=json`)
- `404`: tipo de IP solicitado não encontrado
- `405`: método não permitido
- `429`: limite de requisições por IP atingido

## Estratégia operacional (implantação)

### Por que arquivo `.mmdb` em disco

As bases GeoLite2 são distribuídas como binários **MaxMind DB**. A API usa leitura local por IP — **sem importar os dados para MySQL/PostgreSQL para cada request**, o que preserva latência e simplifica atualizações.

### Configuração de atualização

| Variável | Função |
|----------|--------|
| `GEOIPUPDATE_ACCOUNT_ID_FILE` / `GEOIPUPDATE_LICENSE_KEY_FILE` | Docker Secrets usados somente pelo `geoipupdate`. |
| `GEOIPUPDATE_EDITION_IDS` | `GeoLite2-City GeoLite2-ASN`. |
| `GEOIPUPDATE_FREQUENCY` | `72` horas no compose. |
| `GEOIP_CITY_DATABASE_PATH` / `GEOIP_ASN_DATABASE_PATH` | Caminhos somente leitura no Go; padrões em `/data/geoip/`. |
| `GEOIP_RELOAD_INTERVAL` | `5m` no Go; intervalo para detectar bases alteradas. |

No container Go, os arquivos são montados em `/data/geoip` como volume somente leitura; nenhuma consulta remota ocorre durante requests.

### Fluxo recomendado

1. **Bootstrap:** criar os dois arquivos em `secrets/` conforme [secrets/README.md](../../secrets/README.md).
2. **Rotina:** executar `docker compose up -d --build`; o atualizador oficial mantém o volume atualizado.
3. **Recarga:** o Go valida e troca os leitores automaticamente, mantendo a versão anterior quando uma atualização falha.

### Checklist rápido

- [ ] **City** e **ASN** `.mmdb` no volume Docker `geoip_data`.
- [ ] Docker Secrets do MaxMind definidos no host (nunca commitados).
- [ ] Container `geoipupdate` ativo e sem erros nos logs.
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
    "api_version": "1.6.0",
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
    "api_version": "1.6.0",
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
    "api_version": "1.6.0",
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
