# Quickstart: Validate the Go Rebuild

## Prerequisites

- Docker with Buildx.
- Optional GeoLite2 City and ASN files on the host.
- Port 8080 available locally.

## Build and run

From the repository root, build the Go container:

```bash
docker build -f go/Dockerfile -t public-apis-go:local .
docker run --rm -p 8080:8080 \
  -e APP_TIMEZONE=America/Sao_Paulo \
  -v /path/to/geoip:/data/geoip:ro \
  public-apis-go:local
```

Omit the volume when GeoLite2 is unavailable; the base `getip` response must continue to work. For a public deployment, configure `TRUSTED_PROXY_CIDRS` only with the CIDRs of the reverse proxy in front of the container. On SELinux/Podman hosts, use the volume suffix `:ro,Z`.

## Validate behavior

```bash
curl http://127.0.0.1:8080/getip
curl 'http://127.0.0.1:8080/getip?format=json'
curl 'http://127.0.0.1:8080/getip?format=json&geo=full'
curl 'http://127.0.0.1:8080/getuuid?version=7'
curl -I http://127.0.0.1:8080/docs
curl http://127.0.0.1:8080/docs/getip
```

Expected results are defined in [HTTP contract](./contracts/http-api.md): text or JSON for Get IP, valid UUID v7, a 200 docs hub, and parseable OpenAPI JSON.

## Run quality checks

```bash
cd go
go test ./...
go test -bench=. ./...
```

Run the project load-test command defined during implementation against port 8080. It must meet the p95, p99, throughput, and error-rate targets in [plan.md](./plan.md).
