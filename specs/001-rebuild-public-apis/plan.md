# Implementation Plan: Rebuild Public APIs in Go

**Branch**: `001-rebuild-public-apis` | **Date**: 2026-07-13 | **Spec**: [spec.md](./spec.md)

**Input**: Rebuild the existing public APIs in Go for containerized homelab and public deployment, preserving behavior and Swagger documentation.

## Summary

Build a Go service beside the Laravel application. It preserves `getip`, `getuuid`, the documentation hub, OpenAPI documents, and Swagger routes. The request path uses `net/http`, local GeoLite2 files, and a bounded in-memory per-IP limiter; deployment uses a non-root API container plus the official `geoipupdate` sidecar.

## Technical Context

**Language/Version**: Go 1.26.5

**Primary Dependencies**: Go standard library; `github.com/google/uuid` for UUID v4/v7; `github.com/oschwald/geoip2-golang` for local GeoLite2 MMDB files; pinned Swagger UI static assets.

**Storage**: No application database. GeoLite2 City and ASN `.mmdb` files are maintained by `geoipupdate` in a shared volume and mounted read-only at `/data/geoip`.

**Testing**: `go test ./...`; table-driven unit tests; `httptest` contract and integration tests; repeatable `go test -bench` benchmark plus a load test.

**Target Platform**: Linux `linux/amd64` and `linux/arm64` container, listening on port 8080 behind a trusted reverse proxy when deployed publicly.

**Project Type**: Containerized HTTP web service.

**Performance Goals**: At least 100 successful base-endpoint requests/second at 100 concurrent requests; p95 under 200 ms; p99 under 500 ms; application error rate below 1%.

**Constraints**: Preserve published HTTP contracts and URLs; no per-request network dependencies; configurable trusted proxy CIDRs; default 60 requests per IP per 60 seconds; GeoLite2 failure must not fail `getip`; safe runtime reload; updater credentials stay in Docker Secrets; graceful shutdown.

**Scale/Scope**: One stateless Go container for homelab/public use; no new endpoints, authentication, database, or multi-replica shared rate limiter.

## Constitution Check

*GATE: Passed before research and re-checked after design.*

- [x] Public-contract impact is identified: `getip`, `getuuid`, hub, OpenAPI, Swagger UI, headers, and rate-limit behavior are preserved by contract tests.
- [x] Unit tests cover parsing, response construction, UUID and limiter logic; `httptest` contract tests cover each public route and error path.
- [x] p95, p99, throughput, concurrency, and error-rate targets are measurable through the stated benchmark and load test.
- [x] Request validation, per-IP limits, JSON logs, metrics, trusted proxies, and GeoLite2 graceful degradation are designed.
- [x] Checked-in OpenAPI documents and embedded Swagger UI supply the deployed documentation routes and are verified by tests.

## Project Structure

### Documentation (this feature)

```text
specs/001-rebuild-public-apis/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── contracts/
    └── http-api.md
```

### Source Code (repository root)

```text
go/
├── cmd/public-apis/main.go       # Configuration, HTTP server, shutdown
├── internal/api/                 # Routes, handlers, responses, docs
├── internal/geoip/               # Optional local City/ASN lookup
├── internal/ratelimit/           # Per-IP fixed-window limiter
├── api/openapi/                  # Checked-in Get IP and Get UUID OpenAPI JSON
├── web/swagger-ui/               # Pinned static Swagger UI assets
├── Dockerfile
├── go.mod
└── go.sum
```

Deployment also includes `compose.yaml` and deploy-only instructions in `secrets/README.md`.

**Structure Decision**: Keep the Go rebuild isolated in `go/` so it can be built and deployed alongside `laravel/` until cutover. GeoLite2 updates remain isolated in `geoipupdate`; Laravel is removed only after API parity and cutover validation.
