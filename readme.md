# APIs Públicas

Repositório das minhas APIs públicas para uso em projetos internos, projetos públicos e testes.

Aqui você encontra um índice central com as APIs disponíveis. Para ver como usar cada API, acesse a documentação específica da API desejada.

## Sobre o repositório

- Base principal em Laravel (pasta `laravel/`)
- APIs sem autenticação nesta fase
- Rate limit por IP
- Documentação separada por API em `apis/<nome-da-api>/README.md`

## Endpoint principal

- `https://api.galarca.dev`

## APIs disponíveis

- [`getip`](./apis/getip/README.md): detecta IP do cliente, com suporte a retorno em texto ou JSON e filtros `ipv4` e `ipv6`
- [`getuuid`](./apis/getuuid/README.md): gera UUID válido com suporte às versões 4 e 7

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
curl "http://127.0.0.1:8000/getuuid?version=7"
```

## Estrutura do repositório

- `laravel/`: aplicação principal em Laravel
- `apis/`: pasta de documentação das APIs, uma subpasta por API
- `apis/getip/README.md`: documentação funcional completa da API getip (com exemplos JS, PHP e Node)
- `apis/getuuid/README.md`: documentação funcional completa da API getuuid (com exemplos JS, PHP e Node)
