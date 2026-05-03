# Public APIs - Laravel

Aplicação base em Laravel para publicar APIs do repositório.

## Documentação das APIs

- [`getip`](../apis/getip/README.md) — inclui **estratégia operacional GeoLite2** (bases `.mmdb`, Cron, variáveis de ambiente)
- [`getuuid`](../apis/getuuid/README.md)

## Swagger (UI)

- `https://api.galarca.dev/api/documentation/getip`
- `https://api.galarca.dev/api/documentation/getuuid`

## Desenvolvimento local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

## Produção (resumo)

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Garanta `APP_ENV=production` e `APP_DEBUG=false` no `.env`.

## GeoLite2 / parâmetro `geo` (`GET /getip?format=json&geo…`)

**Contrato:** respostas JSON incluem **`response_code`** e **`meta.server_timezone`** (defina **`APP_TIMEZONE`** no servidor). **`geo`** sem valor ou truthy → localização **minimal**; **`geo=full`** → City completa; dados de rede em **`geo.isp`**.

**Estratégia:** lookup **local** nos binários `.mmdb` (City + ASN + Anonymous IP); atualização por comando Artisan e Scheduler — sem replicação das bases da MaxMind em SQL para cada request.

1. **`MAXMIND_LICENSE_KEY`** no `.env` (conta MaxMind / GeoLite).
2. **Primeira carga ou atualização manual:** `php artisan geoip:update` → grava **GeoLite2-City**, **GeoLite2-ASN** e **GeoLite2-Anonymous-IP** em `storage/app/geoip/` (requer rede e utilitário `tar`).
3. **Rotina:** Cron chamando `php artisan schedule:run`; com **`GEOIP_SCHEDULE_ENABLED=true`** (padrão), `geoip:update` agenda-se **semanalmente** (domingo 04:30, fuso `APP_TIMEZONE`).
4. **Deploy:** após mudar `.env`, `php artisan config:cache`. Os `.mmdb` não são versionados no Git — provisionar via comando ou volume.
5. **Documentação funcional completa:** [apis/getip/README.md](../apis/getip/README.md) (contratos HTTP, exemplos, checklist, atribuição GeoLite2).

Comandos úteis:

```bash
php artisan geoip:update
php artisan geoip:update --edition=GeoLite2-City
php artisan schedule:list
```
