# Public APIs - Laravel

Aplicação base em Laravel para publicar APIs do repositório.

## Documentação das APIs

- [`getip`](../apis/getip/README.md)

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
