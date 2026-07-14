# Tasks: Rebuild Public APIs in Go

**Input**: Design documents from `/specs/001-rebuild-public-apis/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)

**Tests**: Mandatory. Write focused unit tests plus HTTP contract tests before each public behavior.

## Phase 1: Setup

**Purpose**: Create the isolated Go service and reproducible container build.

- [X] T001 Create the Go module and base directories in go/go.mod and go/cmd/public-apis/main.go
- [X] T002 [P] Add the multi-stage non-root container build and ignore rules in go/Dockerfile and go/.dockerignore
- [X] T003 [P] Add pinned Swagger UI static assets and the two checked-in OpenAPI JSON baselines in go/web/swagger-ui/ and go/api/openapi/
- [X] T004 [P] Add developer commands for formatting, tests, benchmarks, and container validation in go/Makefile

---

## Phase 2: Foundational

**Purpose**: Build the shared security, configuration, and server behavior that blocks all endpoints.

- [X] T005 Add configuration parsing, safe defaults, trusted-proxy CIDR validation, server timeouts, and graceful shutdown in go/cmd/public-apis/main.go
- [X] T006 [P] Write unit tests for trusted-proxy client-IP resolution in go/internal/api/clientip_test.go
- [X] T007 Implement client-IP resolution that accepts forwarding headers only from configured trusted proxies in go/internal/api/clientip.go
- [X] T008 [P] Write unit tests for fixed-window per-IP limiting and expiry cleanup in go/internal/ratelimit/limiter_test.go
- [X] T009 Implement the bounded fixed-window per-IP limiter with lazy expiry cleanup in go/internal/ratelimit/limiter.go
- [X] T010 [P] Write unit tests for shared response headers, JSON errors, method handling, and request logging in go/internal/api/middleware_test.go
- [X] T011 Implement method guard, rate-limit, security headers, bounded request handling, structured request logs, and in-process request metrics in go/internal/api/middleware.go
- [X] T012 [P] Write tests that parse the checked-in OpenAPI documents and assert their public paths in go/internal/api/openapi_test.go
- [X] T013 Implement asset embedding and OpenAPI document serving in go/internal/api/docs.go

**Checkpoint**: The Go server starts safely, limits abuse per IP, trusts no spoofed client header, and serves valid OpenAPI files.

---

## Phase 3: User Story 1 - Consultar IP público (Priority: P1) 🎯 MVP

**Goal**: Preserve the complete `getip` public contract, including local optional GeoLite2 enrichment.

**Independent Test**: Run `go test ./internal/api ./internal/geoip` and compare the documented text/JSON, validation, filter, geo, and degradation scenarios through `httptest`.

### Tests for User Story 1

- [X] T014 [P] [US1] Write `getip` HTTP contract tests for text, JSON, filters, validation, methods, and errors in go/internal/api/getip_test.go
- [X] T015 [P] [US1] Write local GeoLite2 missing-data, private-IP, and warning tests in go/internal/geoip/lookup_test.go
- [X] T016 [P] [US1] Add a benchmark for JSON `getip` without GeoLite2 data in go/internal/api/getip_benchmark_test.go

### Implementation for User Story 1

- [X] T017 [US1] Implement `getip` query parsing and compatible text/JSON response construction in go/internal/api/getip.go
- [X] T018 [US1] Implement one-time optional City/ASN MMDB readers and compatible minimal/full response normalization in go/internal/geoip/lookup.go
- [X] T019 [US1] Register `GET /getip` with filter validation, local geo lookup, and graceful degradation in go/internal/api/routes.go
- [X] T020 [US1] Run the `getip` unit, contract, and benchmark checks from go/internal/api/ and go/internal/geoip/

**Checkpoint**: `GET /getip` is independently usable and continues to return its base response when GeoLite2 data is unavailable.

---

## Phase 4: User Story 2 - Generate a UUID (Priority: P1)

**Goal**: Preserve `getuuid` v4/v7 behavior and its no-cache security headers.

**Independent Test**: Run the UUID handler contract tests and verify valid UUID v4/v7 formats, invalid-version behavior, method handling, rate limiting, and headers.

### Tests for User Story 2

- [X] T021 [P] [US2] Write UUID version and invalid-input unit tests in go/internal/api/uuid_test.go
- [X] T022 [P] [US2] Write `getuuid` HTTP contract and response-header tests in go/internal/api/getuuid_test.go
- [X] T023 [P] [US2] Add a UUID endpoint benchmark in go/internal/api/getuuid_benchmark_test.go

### Implementation for User Story 2

- [X] T024 [US2] Add the pinned UUID v4/v7 dependency to go/go.mod and go/go.sum
- [X] T025 [US2] Implement UUID parsing, generation, no-cache headers, and compatible JSON errors in go/internal/api/uuid.go
- [X] T026 [US2] Register `GET /getuuid` with the shared security middleware in go/internal/api/routes.go
- [X] T027 [US2] Run the UUID unit, contract, and benchmark checks from go/internal/api/

**Checkpoint**: `GET /getuuid` independently returns valid v4/v7 UUIDs and rejects invalid or unsafe requests compatibly.

---

## Phase 5: User Story 3 - Discover and integrate the APIs (Priority: P2)

**Goal**: Preserve the documentation hub, OpenAPI paths, and offline interactive Swagger references.

**Independent Test**: Request the two hub URLs, both OpenAPI URLs, and both Swagger UI URLs through `httptest`; verify links and document parsing.

### Tests for User Story 3

- [X] T028 [P] [US3] Write docs hub, OpenAPI, and Swagger UI route tests in go/internal/api/docs_test.go
- [X] T029 [P] [US3] Write contract-drift tests that compare documented endpoint paths and statuses with handlers in go/internal/api/contract_test.go

### Implementation for User Story 3

- [X] T030 [US3] Implement the `/docs` and `/api/documentation` hub pages in go/internal/api/docs.go
- [X] T031 [US3] Implement offline Swagger UI wrappers for `/api/documentation/getip` and `/api/documentation/getuuid` in go/internal/api/docs.go
- [X] T032 [US3] Register the documentation and OpenAPI routes in go/internal/api/routes.go
- [X] T033 [US3] Run the documentation route and contract-drift tests in go/internal/api/

**Checkpoint**: Both APIs are discoverable and their interactive documentation is available without a runtime CDN or second container.

---

## Phase 6: Polish and cross-cutting concerns

**Purpose**: Prove production readiness, performance, and container security.

- [X] T034 Add a repeatable 100-concurrent-request load-test command and result format in go/scripts/loadtest.sh
- [X] T035 Run formatting, `go vet`, all tests, benchmarks, and the load test; record target results in go/README.md
- [X] T036 Build and run the non-root container with and without a read-only GeoLite2 mount using go/Dockerfile
- [X] T037 Update the repository and endpoint documentation for the Go container cutover in readme.md and apis/getip/README.md
- [X] T038 Verify the final contract against the legacy service and document any approved versioned migration in specs/001-rebuild-public-apis/contracts/http-api.md

## Dependencies and execution order

- Setup (T001-T004) precedes Foundational work.
- Foundational work (T005-T013) blocks all endpoint stories.
- US1 and US2 can proceed in parallel after T005-T013; US3 depends on T003 and T013 and can then proceed in parallel.
- Phase 6 starts after all desired stories are complete.

## Parallel opportunities

- T002-T004 can run alongside each other after T001.
- T006, T008, T010, and T012 are independent test-first tasks.
- T014-T016, T021-T023, and T028-T029 can run in parallel within their stories.
- US1, US2, and US3 use different primary files after the foundation is complete.

## Implementation strategy

### MVP first

1. Complete Setup and Foundational phases.
2. Complete US1 and validate the `getip` compatibility contract.
3. Complete US2 to preserve both public API endpoints.
4. Add US3 before public cutover so the existing documentation experience remains intact.

### Security and performance gates

- Do not trust forwarding headers without configured trusted proxy CIDRs.
- Do not add a cache, database, Redis, or second container unless measurement or multi-replica deployment makes it necessary.
- Do not release until the load test meets the p95, p99, throughput, and error-rate goals and the container runs as non-root.
