#!/bin/sh
set -eu
url="${1:-http://127.0.0.1:8080/getuuid}"
requests="${REQUESTS:-1000}"
concurrency="${CONCURRENCY:-100}"
results="$(mktemp)"
trap 'rm -f "$results"' EXIT
seq 1 "$requests" | xargs -P "$concurrency" -I{} curl -sS -o /dev/null -w '%{http_code} %{time_total}\n' "$url" >"$results"
failures="$(awk '$1 != 200 {n++} END {print n+0}' "$results")"
sort -n -k2 "$results" >"$results.sorted"
trap 'rm -f "$results" "$results.sorted"' EXIT
awk -v total="$requests" -v failures="$failures" 'NR==int(total*.95+0.999){p95=$2} NR==int(total*.99+0.999){p99=$2} END{printf "requests=%d failures=%d error_rate=%.2f%% p95_ms=%.2f p99_ms=%.2f\n",total,failures,failures*100/total,p95*1000,p99*1000}' "$results.sorted"
test "$failures" -eq 0
