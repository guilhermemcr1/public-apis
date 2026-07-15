# APIs Públicas — Laravel e Go

Repositório das minhas APIs públicas para uso em projetos internos, projetos públicos e testes.

Este repositório mantém a implementação original em Laravel e o rebuild equivalente em Go. O rebuild existe para testar a linguagem, aprender padrões de implementação e validar uma alternativa de alta performance para execução em container.

## Sobre o repositório

- Laravel em `laravel/` e rebuild em Go em `go/`
- O Go é uma implementação experimental/educacional para aprendizado, testes de linguagem e evolução da API
- APIs sem autenticação até o momento
- Rate limit por IP para evitar abuso e sobrecarga
- Documentação separada por API em `apis/<nome-da-api>/README.md`
- Geolocalização opcional na API **getip** (`format=json&geo` ou `geo=full`) via bases **GeoLite2** em disco (`.mmdb`); ver secção abaixo e [apis/getip/README.md](./apis/getip/README.md)
- Guia de cutover e rollback em [MIGRATION.md](./MIGRATION.md)

## Endpoint principal

- `https://api.galarca.dev`

## Swagger (documentação interativa)

- Índice com todas as APIs: [https://api.galarca.dev/docs](https://api.galarca.dev/docs) (equivalente: [https://api.galarca.dev/api/documentation](https://api.galarca.dev/api/documentation))
- Get IP: [https://api.galarca.dev/api/documentation/getip](https://api.galarca.dev/api/documentation/getip)
- Get UUID: [https://api.galarca.dev/api/documentation/getuuid](https://api.galarca.dev/api/documentation/getuuid)

## APIs disponíveis

- `[getip](./apis/getip/README.md)` (**v1.6.0**): texto ou JSON com **`response_code`** e **`meta.server_timezone`**; filtros `ipv4` / `ipv6`; **`geo`** opcional (`minimal` ou **`geo=full`**) + **`geo.isp`** quando os `.mmdb` GeoLite2 estão instalados
- `[getuuid](./apis/getuuid/README.md)`: gera UUID válido com suporte às versões 4 e 7

## Estratégia operacional — GeoLite2 (getip + `geo`)

Objetivo: manter lookups **rápidos e locais** usando arquivos **`.mmdb`** (sem replicação das bases em SQL para cada request).

| Passo | Ação |
|--------|------|
| Credenciais | Guardar Account ID e License Key como Docker Secrets; nunca na API Go ou no Git ([detalhes MaxMind](https://dev.maxmind.com/geoip/updating-databases/)). |
| Primeira instalação | Criar `secrets/maxmind_account_id.txt` e `secrets/maxmind_license_key.txt`, depois executar `docker compose up -d --build`. |
| Atualizações | O serviço oficial `geoipupdate` baixa City e ASN a cada 72 horas no volume compartilhado; a API Go recarrega arquivos válidos automaticamente. |
| Deploy | Usar [compose.yaml](./compose.yaml). Os `.mmdb` ficam no volume Docker e não entram no Git. |
| Compliance | Respeitar [termos/atribuição GeoLite2](https://dev.maxmind.com/geoip/geolite2-free-data) nos materiais públicos que mencionem os dados. |

Documentação funcional completa, exemplos de payload e edge cases: **[apis/getip/README.md](./apis/getip/README.md)**. Resumo técnico Laravel: **[laravel/README.md](./laravel/README.md)**.

## Executar localmente

Go:

```bash
cd go
go run ./cmd/public-apis
```

Container Go:

```bash
docker build -f go/Dockerfile -t public-apis-go:local .
docker run --rm -p 8080:8080 public-apis-go:local
```

Implementação Laravel de referência:

```bash
cd laravel
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

Teste rápido:

```bash
curl "http://127.0.0.1:8000/getip?format=json"
curl "http://127.0.0.1:8000/getip?format=json&geo=1"
curl "http://127.0.0.1:8000/getip?format=json&geo=full"
curl "http://127.0.0.1:8000/getuuid?version=7"
```

## Estrutura do repositório

- `go/`: rebuild principal em Go, Dockerfile, testes e Swagger offline
- `laravel/`: implementação Laravel mantida como referência de contrato
- `apis/`: pasta de documentação das APIs, uma subpasta por API
- `apis/getip/README.md`: documentação funcional completa da API getip (texto/JSON, `ipv4`/`ipv6`, opcional `geo` + GeoLite2, exemplos JS/PHP/Node, estratégia operacional)
- `apis/getuuid/README.md`: documentação funcional completa da API getuuid (com exemplos JS, PHP e Node)
- `compose.yaml`: serviço Go e updater oficial do GeoLite2
- `secrets/README.md`: preparação dos secrets de deploy (não versionados)
