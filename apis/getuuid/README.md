# API `getuuid`

Documentação da API pública de geração de UUID.

## Base URL

- Produção: `https://api.galarca.dev`
- Local: `http://127.0.0.1:8000`

## Endpoint

### `GET /getuuid`

Gera e retorna um UUID válido em formato JSON.

#### Query params suportados

- `version=4`: gera UUID v4
- `version=7`: gera UUID v7
- sem `version`: fallback para UUID v4

#### Respostas esperadas

- `200`: sucesso
- `400`: versão inválida
- `405`: método não permitido
- `429`: limite de requisições por IP atingido

## Segurança aplicada

- Apenas métodos `GET` e `OPTIONS` são aceitos; demais métodos retornam `405`.
- Rate limit por IP com resposta padronizada em `429`.
- Validação estrita do parâmetro `version` (aceita somente `4` ou `7`).
- Headers de segurança no retorno:
  - `X-Content-Type-Options: nosniff`
  - `X-Robots-Tag: noindex`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'`
- Respostas com `no-store`/`no-cache` para evitar cache indevido de payloads.

## Exemplos de resposta da API

### `GET /getuuid`

```json
{
  "uuid": "3f0f0bd0-5718-4f0b-b1f7-9a0ed5d1de53",
  "version": 4
}
```

### `GET /getuuid?version=7`

```json
{
  "uuid": "01962a7c-7d42-7f52-a0f0-9d52b9d4e2c1",
  "version": 7
}
```

### `GET /getuuid?version=9` (erro esperado)

```json
{
  "error": "Versão de UUID inválida. Use apenas 4 ou 7.",
  "status": 400
}
```

## Exemplos de uso

### JavaScript (browser)

```js
const url = 'https://api.galarca.dev/getuuid?version=7';

async function getUuid() {
  const response = await fetch(url, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Falha ao gerar UUID: ${response.status}`);
  }

  const data = await response.json();
  console.log('UUID:', data.uuid);
  console.log('Versão:', data.version);
}

getUuid().catch(console.error);
```

### PHP (cURL)

```php
<?php

$url = 'https://api.galarca.dev/getuuid?version=4';

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

echo "UUID: {$data['uuid']}" . PHP_EOL;
echo "Versão: {$data['version']}" . PHP_EOL;
```

### Node.js (nativo com fetch)

```js
const url = 'https://api.galarca.dev/getuuid';

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
curl "https://api.galarca.dev/getuuid"
curl "https://api.galarca.dev/getuuid?version=4"
curl "https://api.galarca.dev/getuuid?version=7"
curl "https://api.galarca.dev/getuuid?version=9"
```
