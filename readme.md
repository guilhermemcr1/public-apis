# APIs Públicas

Repositório das minhas APIs públicas para uso em projetos internos, projetos públicos e testes.

Aqui você encontra um índice central com as APIs disponíveis. Para ver como usar cada API, acesse a documentação específica da API desejada.

## Sobre o repositório

- Base principal em Laravel (pasta `laravel/`)
- APIs sem autenticação até o momento
- Rate limit por IP para evitar abuso e sobrecarga
- Documentação separada por API em `apis/<nome-da-api>/README.md`
- Geolocalização opcional na API **getip** (`format=json&geo` ou `geo=full`) via bases **GeoLite2** em disco (`.mmdb`); ver secção abaixo e [apis/getip/README.md](./apis/getip/README.md)

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
| Credenciais | Definir `MAXMIND_LICENSE_KEY` no `.env` da app Laravel ([detalhes MaxMind](https://dev.maxmind.com/geoip/updating-databases/)). |
| Primeira instalação | Na pasta `laravel/`: `php artisan geoip:update` (baixa **GeoLite2-City** e **GeoLite2-ASN** para `storage/app/geoip/`). |
| Atualizações | Scheduler Laravel: `geoip:update` semanal (domingo 04:30) quando `GEOIP_SCHEDULE_ENABLED=true`; em produção o Cron deve executar `php artisan schedule:run` com a periodicidade habitual (ex.: a cada minuto). Desative o agendamento se preferir só atualização manual. |
| Deploy | Após alterar `.env`: `php artisan config:cache`. Os `.mmdb` não vão para o Git (volume ou comando pós-deploy). |
| Compliance | Respeitar [termos/atribuição GeoLite2](https://dev.maxmind.com/geoip/geolite2-free-data) nos materiais públicos que mencionem os dados. |

Documentação funcional completa, exemplos de payload e edge cases: **[apis/getip/README.md](./apis/getip/README.md)**. Resumo técnico Laravel: **[laravel/README.md](./laravel/README.md)**.

## Executar localmente

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

- `laravel/`: aplicação principal em Laravel
- `apis/`: pasta de documentação das APIs, uma subpasta por API
- `apis/getip/README.md`: documentação funcional completa da API getip (texto/JSON, `ipv4`/`ipv6`, opcional `geo` + GeoLite2, exemplos JS/PHP/Node, estratégia operacional)
- `apis/getuuid/README.md`: documentação funcional completa da API getuuid (com exemplos JS, PHP e Node)

