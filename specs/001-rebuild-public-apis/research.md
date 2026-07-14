# Research: Rebuild Public APIs in Go

## HTTP service and lifecycle

**Decision**: Use Go 1.26.5 and `net/http` with `ServeMux`, small explicit middleware, server timeouts, `signal.NotifyContext`, and graceful shutdown.

**Rationale**: Two endpoints and static documentation do not justify a web framework. The standard library provides routing, request contexts, `httptest`, and controlled server shutdown with fewer dependencies.

**Alternatives considered**: Gin and chi add an abstraction without a required capability; Fiber changes the HTTP stack without a demonstrated benefit.

## UUID generation

**Decision**: Use `github.com/google/uuid` for UUID v4 and v7.

**Rationale**: Go's standard library does not generate UUIDs. This focused dependency avoids implementing random-bit and version-format rules while preserving the existing output contract.

**Alternatives considered**: A hand-written UUID implementation is unnecessary security-sensitive code; a broader web framework does not solve this need.

## GeoLite2 behavior

**Decision**: Open optional City and ASN MMDB files once from a read-only mount; never download or query a remote GeoIP service during a request.

**Rationale**: Local lookups meet the latency target and retain the existing graceful-degradation contract when files are absent, invalid, or incomplete.

**Alternatives considered**: A remote GeoIP API adds request latency and an availability dependency; importing MMDB data into a database adds operations without serving this read-only lookup use case.

## Rate limit and client IP

**Decision**: Implement a fixed 60-second in-memory per-IP limiter with lazy expiry cleanup. Derive the client from `RemoteAddr`; honor forwarding headers only when the peer matches configured trusted-proxy CIDRs.

**Rationale**: This is compatible with the existing default of 60 requests per minute and is sufficient for a single container. Trusting headers from arbitrary clients would allow rate-limit bypasses.

**Alternatives considered**: Redis is unnecessary for one replica. If multiple replicas are introduced, move the limit to the reverse proxy or a shared store.

## Swagger and OpenAPI

**Decision**: Keep two checked-in OpenAPI JSON documents and embed one pinned Swagger UI static distribution in the Go binary. Serve the existing hub and interactive-documentation paths from the same container.

**Rationale**: This works offline in a homelab, avoids runtime CDN or generator dependencies, and makes the published contract reviewable and testable.

**Alternatives considered**: Annotation/code generation makes the contract implicit; a dedicated Swagger container adds an unnecessary deployable; CDN assets require network access at runtime.

## Container image

**Decision**: Use a multi-stage Docker build, compile a static Go binary, and run it as a non-root user in a minimal runtime image. Mount GeoLite2 files read-only and keep timezone data available for `America/Sao_Paulo`.

**Rationale**: The production image contains only the executable, assets, and runtime data it needs, reducing image size and attack surface while fitting the homelab deployment model.

**Alternatives considered**: Running the Go SDK in production increases image size; a second container for GeoLite updates is deferred until an operational need exists.

## Sources

- [Go release history](https://go.dev/doc/devel/release)
- [Go 1.26 release notes](https://go.dev/doc/go1.26)
- [Swagger UI](https://swagger.io/open-source/swagger-ui/)
- [Docker multi-stage builds](https://docs.docker.com/build/building/multi-stage/)
