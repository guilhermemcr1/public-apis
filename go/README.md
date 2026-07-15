# Public APIs Go

Rebuild em Go das APIs `getip` e `getuuid`, com Swagger offline, rate limit por IP e GeoLite2 local opcional.

## Executar

```bash
go run ./cmd/public-apis
```

Variáveis principais:

- `PORT` (padrão `8080`)
- `APP_TIMEZONE` (padrão `America/Sao_Paulo`)
- `GETIP_RATE_LIMIT` e `GETUUID_RATE_LIMIT` (padrão `60`)
- `RATE_WINDOW_SECONDS` (padrão `60`)
- `TRUSTED_PROXY_CIDRS` (lista CIDR separada por vírgulas)
- `GEOIP_CITY_DATABASE_PATH` e `GEOIP_ASN_DATABASE_PATH`
- `GEOIP_RELOAD_INTERVAL` (padrão `5m`): intervalo para recarregar bases alteradas

Headers encaminhados só são aceitos quando `RemoteAddr` pertence a `TRUSTED_PROXY_CIDRS`.

## Container

```bash
docker build -f go/Dockerfile -t public-apis-go:local .
docker run --rm -p 8080:8080 \
  -e TRUSTED_PROXY_CIDRS=172.16.0.0/12 \
  -v /path/to/geoip:/data/geoip:ro \
  public-apis-go:local
```

O container executa como usuário não-root. As bases MMDB não são incluídas na imagem.
Em hosts com SELinux e Podman, use `:ro,Z` no volume.

## Validação

```bash
make check
GETUUID_RATE_LIMIT=100000 go run ./cmd/public-apis
REQUESTS=1000 CONCURRENCY=100 sh scripts/loadtest.sh
```

Resultado local de referência em 2026-07-13: 1.000 requests, concorrência 100, 0 erros, p95 0,89 ms e p99 1,69 ms.

## Atualização automática GeoLite2

Em produção, use o `compose.yaml` da raiz. O serviço `geoipupdate` mantém City e ASN em um volume compartilhado; a API Go monta esse volume como somente leitura e recarrega leitores válidos sem reiniciar.
