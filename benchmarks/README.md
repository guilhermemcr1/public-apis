# Benchmark: rebuild Go × Laravel

Este benchmark compara as duas implementações existentes neste repositório. Ele mede o projeto como está executado, não a capacidade máxima teórica de Go, PHP ou Laravel.

## Metodologia

- Mesma máquina, rede local e ferramenta cliente (`ab`).
- Um container executado por vez, limitado a **2 CPUs** e **512 MiB**; Nginx e PHP-FPM compartilham esse limite.
- **5 rodadas** de 5.000 requisições por rota, concorrência 50 e 200 requisições de aquecimento antes de cada amostra.
- Keep-alive habilitado; rate limit permanece no caminho da requisição, com teto elevado para não produzir respostas `429`.
- Rotas medidas: `GET /getip?format=json` e `GET /getuuid?version=7`.
- Bases GeoLite2 não entram no teste, para que o resultado não dependa de arquivos externos.

O container Laravel usa PHP-FPM 8.4 com oito workers estáticos, Nginx, OPcache, autoload autoritativo e cache de configuração. Os processos rodam como usuário não-root no mesmo container. O rate limit continua usando cache de arquivo, preservando o comportamento seguro do projeto.

## Reproduzir

Requisitos: Go não é necessário para rodar o teste; são usados Docker, `curl` e ApacheBench (`ab`). As imagens são construídas do zero.

```bash
./benchmarks/run.sh
```

Para mudar a carga:

```bash
REQUESTS=10000 CONCURRENCY=100 RUNS=5 ./benchmarks/run.sh
```

O Docker é o engine padrão. O Dockerfile também é compatível com Podman:

```bash
CONTAINER_ENGINE=podman ./benchmarks/run.sh
```

Cada execução cria uma pasta em `benchmarks/results/` com:

- `summary.csv`: métricas estruturadas para planilhas e gráficos;
- `environment.txt`: hardware, sistema e parâmetros do teste;
- `raw/*.txt`: saída integral de cada amostra do ApacheBench.

## Como interpretar

- `requests_per_second`: vazão; maior é melhor.
- `p50_ms`, `p95_ms` e `p99_ms`: latência por percentil; menor é melhor.
- `failed`: deve ser zero. Uma execução com falhas não deve ser usada na comparação.

Para resultados honestos de portfólio, publique a mediana das rodadas, mantenha os dados brutos e sempre cite hardware, limites e servidor usado.

## Resultado principal — PHP-FPM, 14/07/2026

Ambiente: AMD Ryzen 5 7600, Fedora/Linux, PHP 8.4.23, Nginx 1.30.3 e OPcache. A coleta foi executada com Podman 5.8.4 porque o usuário da sessão ainda não tinha acesso ao socket Docker. Os containers foram limitados a 2 CPUs e 512 MiB. Os valores abaixo são as medianas de cinco rodadas; todas as 100.000 requisições medidas retornaram `2xx`, sem falhas.

| Rota | Implementação | Requisições/s | p50 | p95 |
|---|---:|---:|---:|---:|
| `getip` | Go | 91.347,56 | 1 ms | 1 ms |
| `getip` | Laravel + FPM | 926,19 | 70 ms | 90 ms |
| `getuuid` | Go | 88.933,16 | 1 ms | 1 ms |
| `getuuid` | Laravel + FPM | 897,93 | 71 ms | 90 ms |

Neste ambiente específico, o rebuild Go entregou aproximadamente **99×** a vazão do Laravel com PHP-FPM nas duas rotas. Em relação ao cenário antigo com `artisan serve`, o PHP-FPM aumentou a mediana do Laravel em aproximadamente **24%** no `getip` e **22%** no `getuuid`. As imagens produzidas tinham 13,5 MiB (Go) e 143,2 MiB (Laravel).

Mesmo com workers persistentes e bytecode em cache, o Laravel ainda inicializa o framework por requisição e atualiza o rate limit em cache de arquivo. Octane ou um cache compartilhado em memória seriam cenários diferentes e devem ser medidos separadamente.

O ApacheBench registra percentis em milissegundos inteiros e pode se tornar o próprio limite do cliente quando o servidor ultrapassa dezenas de milhares de requisições por segundo. Os números Go devem ser lidos como vazão observada neste ensaio, não como teto absoluto do serviço.

Dados PHP-FPM auditáveis: [summary.csv](./results/2026-07-14_php-fpm/summary.csv), [ambiente](./results/2026-07-14_php-fpm/environment.txt) e [saídas brutas](./results/2026-07-14_php-fpm/raw/).

O cenário anterior com `artisan serve` foi preservado apenas como histórico em [2026-07-14_portfolio](./results/2026-07-14_portfolio/).
