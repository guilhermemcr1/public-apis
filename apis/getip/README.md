# API `getip`

Documentacao da API publica de deteccao de IP.

## Base URL

- Producao: `https://api.galarca.dev`
- Local: `http://127.0.0.1:8000`

## Endpoint

### `GET /getip`

Retorna o IP publico detectado do cliente.

#### Query params suportados

- `format=json`: retorna payload JSON
- `ipv4`: exige resposta IPv4
- `ipv6`: exige resposta IPv6

#### Respostas esperadas

- `200`: sucesso
- `400`: parametros invalidos (ex.: `ipv4` e `ipv6` juntos)
- `404`: tipo de IP solicitado nao encontrado
- `405`: metodo nao permitido
- `429`: limite de requisicoes por IP atingido

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
  console.log('Versao:', data.version);
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
echo "Versao: {$data['version']}" . PHP_EOL;
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

## Testes rapidos com curl

```bash
curl "https://api.galarca.dev/getip"
curl "https://api.galarca.dev/getip?format=json"
curl "https://api.galarca.dev/getip?format=json&ipv4"
curl "https://api.galarca.dev/getip?format=json&ipv6"
```
