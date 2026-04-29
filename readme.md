# APIs Publicas

Repositorio destinado ao upload das APIs publicas que eu criei para utilizacao diaria em projetos internos, projetos publicos e testes.

As APIs deste repositorio estao disponiveis para teste e utilizacao.

## Visao geral

- Base principal em Laravel (pasta `laravel/`)
- APIs sem autenticacao nesta fase
- Rate limit por IP
- Estrutura preparada para adicionar novas APIs com o tempo

## API disponivel

- [`getip`](./apis/getip/README.md): detecta IP do cliente, com suporte a retorno em texto ou JSON, filtros `ipv4` e `ipv6`

## Endpoint em producao

- `GET https://api.galarca.dev/getip`
- `GET https://api.galarca.dev/getip?format=json`

Exemplos prontos de consumo em JavaScript, PHP e Node.js estao na documentacao da API:

- [`apis/getip/README.md`](./apis/getip/README.md)

## Executar localmente

```bash
cd laravel
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

Teste rapido:

```bash
curl "http://127.0.0.1:8000/getip?format=json"
```

## Estrutura do repositorio

- `laravel/`: aplicacao principal em Laravel
- `apis/`: pasta de documentacao das APIs, uma subpasta por API
- `apis/getip/README.md`: documentacao funcional completa da API getip (com exemplos JS, PHP e Node)

## Roadmap

- Adicionar novas APIs publicas e internas
- Publicar documentacao central em Docusaurus
