# Migração do Laravel para Go

O Laravel continua ativo durante a validação. O corte acontece apenas depois que o Go responder corretamente pelo endereço público.

## 1. Preparar o host

Requisitos: Docker com Compose, credenciais GeoLite2 da MaxMind e acesso à configuração do reverse proxy.

```bash
cp .env.example .env
mkdir -p secrets
printf '%s' 'SEU_ACCOUNT_ID' > secrets/maxmind_account_id.txt
printf '%s' 'SUA_LICENSE_KEY' > secrets/maxmind_license_key.txt
chmod 600 secrets/maxmind_*.txt
```

Mantenha `PUBLIC_APIS_BIND=127.0.0.1` e `PUBLIC_APIS_PORT=3000` enquanto Laravel e Go estiverem rodando juntos. Se houver reverse proxy, configure em `.env` somente o IP ou CIDR dele em `TRUSTED_PROXY_CIDRS`; não confie em redes maiores que o necessário.

## 2. Subir e validar o Go

```bash
docker compose up -d --build
docker compose ps
docker compose logs --tail=100 api geoipupdate
curl -fsS http://127.0.0.1:3000/
curl -fsS 'http://127.0.0.1:3000/getip?format=json'
curl -fsS 'http://127.0.0.1:3000/getuuid?version=7'
curl -fsS http://127.0.0.1:3000/docs/getip
```

Confirme que o updater baixou `GeoLite2-City` e `GeoLite2-ASN`. Pelo domínio público, teste também `getip?format=json&geo=full` e confirme que não aparecem `city_database_unavailable` ou `isp_database_unavailable`.

## 3. Fazer o cutover

1. Troque o upstream do reverse proxy da porta Laravel para `127.0.0.1:3000`.
2. Recarregue o reverse proxy sem interromper conexões.
3. Teste `/getip`, `/getuuid`, `/docs` e `/api/documentation` pelo domínio público.
4. Acompanhe `docker compose logs -f api` e confirme ausência de respostas 5xx ou picos de 429.

Não publique a porta Go diretamente na internet quando ela já estiver protegida pelo reverse proxy/firewall.

## 4. Rollback

Se houver incompatibilidade, restaure o upstream anterior do Laravel e recarregue o reverse proxy. Mantenha os containers Go ativos para diagnóstico; o rollback não exige alterar dados.

## 5. Remover o Laravel

Depois do período de observação definido para o homelab:

1. Faça backup da configuração Laravel e do reverse proxy.
2. Confirme que nenhum serviço ainda referencia a porta ou o container Laravel.
3. Remova o deploy Laravel e suas dependências PHP-FPM/Nginx exclusivas.
4. Opcionalmente altere `PUBLIC_APIS_PORT` para outra porta; mantenha `3000` se o reverse proxy já estiver configurado e protegido.

Nunca remova `laravel/` do Git antes de marcar uma versão ou tag que permita consultar a implementação de referência.
