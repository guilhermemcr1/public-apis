#!/usr/bin/env sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
ENGINE=${CONTAINER_ENGINE:-docker}
REQUESTS=${REQUESTS:-5000}
CONCURRENCY=${CONCURRENCY:-50}
RUNS=${RUNS:-5}
CPUS=${CPUS:-2}
MEMORY=${MEMORY:-512m}
PORT=${PORT:-18080}
STAMP=${STAMP:-$(date +%Y-%m-%d_%H%M%S)}
RESULT_DIR="$ROOT/benchmarks/results/$STAMP"
CSV="$RESULT_DIR/summary.csv"
CONTAINER=public-apis-benchmark

command -v "$ENGINE" >/dev/null 2>&1 || { echo "Erro: $ENGINE não encontrado." >&2; exit 1; }
"$ENGINE" info >/dev/null 2>&1 || { echo "Erro: não foi possível acessar o daemon/socket do $ENGINE." >&2; exit 1; }
command -v ab >/dev/null 2>&1 || { echo "Erro: ApacheBench (ab) não encontrado." >&2; exit 1; }

mkdir -p "$RESULT_DIR/raw"
printf '%s\n' 'implementation,endpoint,run,requests,concurrency,complete,failed,non_2xx,seconds,requests_per_second,p50_ms,p95_ms,p99_ms,max_ms' > "$CSV"

cleanup() {
    "$ENGINE" rm -f "$CONTAINER" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

build_images() {
    "$ENGINE" build -f "$ROOT/go/Dockerfile" -t public-apis-go:benchmark "$ROOT"
    "$ENGINE" build -f "$ROOT/benchmarks/Dockerfile.laravel" -t public-apis-laravel:benchmark "$ROOT/laravel"
}

wait_ready() {
    attempt=0
    until curl -fsS "http://127.0.0.1:$PORT/getuuid?version=7" >/dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge 60 ]; then
            echo "Erro: container não ficou pronto." >&2
            "$ENGINE" logs "$CONTAINER" >&2 || true
            exit 1
        fi
        sleep 1
    done
}

start_container() {
    implementation=$1
    image=$2
    cleanup
    "$ENGINE" run -d --name "$CONTAINER" \
        --cpus "$CPUS" --memory "$MEMORY" --log-driver none \
        -p "127.0.0.1:$PORT:8080" \
        -e GETIP_RATE_LIMIT=1000000 \
        -e GETUUID_RATE_LIMIT=1000000 \
        "$image" >/dev/null
    wait_ready
    printf 'Executando %s...\n' "$implementation"
}

percentile() {
    file=$1
    wanted=$2
    awk -v p="$wanted" '$1 == p"%" { print $2; exit }' "$file"
}

run_case() {
    implementation=$1
    endpoint=$2
    path=$3
    run=$4
    raw="$RESULT_DIR/raw/${implementation}_${endpoint}_${run}.txt"

    ab -k -q -n 200 -c 20 "http://127.0.0.1:$PORT$path" >/dev/null
    ab -k -n "$REQUESTS" -c "$CONCURRENCY" "http://127.0.0.1:$PORT$path" > "$raw"
    sed -i 's/[[:space:]]*$//' "$raw"

    complete=$(awk -F: '/Complete requests:/ { gsub(/[[:space:]]/, "", $2); print $2 }' "$raw")
    failed=$(awk -F: '/Failed requests:/ { gsub(/[[:space:]]/, "", $2); print $2 }' "$raw")
    non_2xx=$(awk -F: '/Non-2xx responses:/ { gsub(/[[:space:]]/, "", $2); print $2 }' "$raw")
    non_2xx=${non_2xx:-0}
    seconds=$(awk -F: '/Time taken for tests:/ { gsub(/seconds/, "", $2); gsub(/[[:space:]]/, "", $2); print $2 }' "$raw")
    rps=$(awk -F: '/Requests per second:/ { split($2, a, " "); print a[1] }' "$raw")
    p50=$(percentile "$raw" 50)
    p95=$(percentile "$raw" 95)
    p99=$(percentile "$raw" 99)
    max=$(percentile "$raw" 100)

    if [ "$complete" -ne "$REQUESTS" ] || [ "$failed" -ne 0 ] || [ "$non_2xx" -ne 0 ]; then
        echo "Erro: amostra inválida em $raw (complete=$complete, failed=$failed, non_2xx=$non_2xx)." >&2
        exit 1
    fi

    printf '%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n' \
        "$implementation" "$endpoint" "$run" "$REQUESTS" "$CONCURRENCY" \
        "$complete" "$failed" "$non_2xx" "$seconds" "$rps" "$p50" "$p95" "$p99" "$max" >> "$CSV"
}

benchmark_image() {
    implementation=$1
    image=$2
    start_container "$implementation" "$image"
    run=1
    while [ "$run" -le "$RUNS" ]; do
        run_case "$implementation" getip '/getip?format=json' "$run"
        run_case "$implementation" getuuid '/getuuid?version=7' "$run"
        run=$((run + 1))
    done
    cleanup
}

write_environment() {
    {
        printf 'timestamp=%s\n' "$(date --iso-8601=seconds 2>/dev/null || date)"
        printf 'kernel=%s\n' "$(uname -srmo)"
        printf 'cpu=%s\n' "$(awk -F: '/model name/ { sub(/^[[:space:]]*/, "", $2); print $2; exit }' /proc/cpuinfo)"
        printf 'logical_cpus=%s\n' "$(getconf _NPROCESSORS_ONLN)"
        printf 'memory_kib=%s\n' "$(awk '/MemTotal/ { print $2 }' /proc/meminfo)"
        printf 'container_engine=%s\n' "$($ENGINE --version)"
        printf 'apachebench=%s\n' "$(ab -V 2>&1 | awk '/Version/ { print $5; exit }')"
        printf 'go_image_bytes=%s\n' "$($ENGINE image inspect public-apis-go:benchmark --format '{{.Size}}')"
        printf 'laravel_image_bytes=%s\n' "$($ENGINE image inspect public-apis-laravel:benchmark --format '{{.Size}}')"
        printf 'laravel_runtime=nginx+php-fpm\n'
        printf 'php_version=%s\n' "$($ENGINE run --rm --entrypoint php public-apis-laravel:benchmark -r 'echo PHP_VERSION;')"
        printf 'nginx_version=%s\n' "$($ENGINE run --rm --entrypoint nginx public-apis-laravel:benchmark -v 2>&1 | awk -F/ '{ print $2 }')"
        printf 'php_fpm_workers=8\nopcache=enabled\n'
        printf 'requests=%s\nconcurrency=%s\nruns=%s\ncpus=%s\nmemory=%s\n' \
            "$REQUESTS" "$CONCURRENCY" "$RUNS" "$CPUS" "$MEMORY"
    } > "$RESULT_DIR/environment.txt"
}

cd "$ROOT"
build_images
write_environment
benchmark_image go public-apis-go:benchmark
benchmark_image laravel public-apis-laravel:benchmark

printf '\nResultados: %s\n' "$CSV"
