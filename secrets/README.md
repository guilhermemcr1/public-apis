# Docker Secrets do MaxMind

Crie estes arquivos somente no host de deploy, nunca no Git:

```bash
mkdir -p secrets
printf '%s' 'SEU_ACCOUNT_ID' > secrets/maxmind_account_id.txt
printf '%s' 'SUA_LICENSE_KEY' > secrets/maxmind_license_key.txt
chmod 600 secrets/maxmind_*.txt
```

Suba a API e o atualizador:

```bash
docker compose up -d --build
```

O `geoipupdate` atualiza City e ASN a cada 72 horas no volume compartilhado. A API Go monta o volume somente para leitura e recarrega bases válidas automaticamente a cada 5 minutos.
