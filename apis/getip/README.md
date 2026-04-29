# API `getip`

Documentação da API pública de detecção de IP.

## Base URL

- Produção: `https://api.galarca.dev`
- Local: `http://127.0.0.1:8000`

## Endpoint

### `GET /getip`

Retorna o IP público detectado do cliente.

#### Query params suportados

- `format=json`: retorna payload JSON
- `ipv4`: exige resposta IPv4
- `ipv6`: exige resposta IPv6

#### Respostas esperadas

- `200`: sucesso
- `400`: parâmetros inválidos (ex.: `ipv4` e `ipv6` juntos)
- `404`: tipo de IP solicitado não encontrado
- `405`: método não permitido
- `429`: limite de requisições por IP atingido

## Exemplos de resposta da API

### `GET /getip`

```txt
203.0.113.10
```

### `GET /getip?format=json`

```json
{
  "ip": "203.0.113.10",
  "version": "v4",
  "private": false,
  "meta": {
    "api": "IP Detection API",
    "api_version": "1.2.1",
    "timestamp": "2026-04-29T20:30:00+00:00"
  }
}
```

### `GET /getip?format=json&ipv4&ipv6` (erro esperado)

```json
{
  "error": "Use apenas ?ipv4 ou ?ipv6, não ambos simultaneamente.",
  "status": 400
}
```

### `POST /getip?format=json` (erro esperado)

```json
{
  "error": "Method Not Allowed. Use GET.",
  "status": 405
}
```

## Exemplos de uso

### JavaScript (browser)

```js
const url = 'https://api.galarca.dev/getip?format=json';

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
  console.log('IP:', data.ip);
  console.log('Versão:', data.version);
}

getPublicIp().catch(console.error);
```

### PHP (cURL)

```php
<?php

$url = 'https://api.galarca.dev/getip?format=json';

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

echo "IP: {$data['ip']}" . PHP_EOL;
echo "Versão: {$data['version']}" . PHP_EOL;
```

### Node.js (nativo com fetch)

```js
const url = 'https://api.galarca.dev/getip?format=json';

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
```
