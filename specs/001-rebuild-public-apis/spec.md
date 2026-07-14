# Feature Specification: Rebuild Public APIs

**Feature Branch**: `001-rebuild-public-apis`

**Created**: 2026-07-13

**Status**: Draft

**Input**: User description: "Refaça o build do projeto atual de APIs, mantendo
as características existentes, incluindo Swagger."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consultar IP público (Priority: P1)

As a client application, I can obtain my detected public IP in text or JSON,
optionally request IPv4/IPv6 filtering and geographic enrichment, so that I can
use the information without changing my existing integration.

**Why this priority**: `getip` is an existing public API and must remain usable
throughout the rebuild.

**Independent Test**: Send valid and invalid requests to `/getip` and compare
status, response format, documented fields, and error behavior to the current
public contract.

**Acceptance Scenarios**:

1. **Given** a client requests `GET /getip`, **When** an IP is detected,
   **Then** it receives the IP as plain text.
2. **Given** a client requests `GET /getip?format=json`, **When** an IP is
   detected, **Then** it receives the documented JSON payload, including
   `response_code` and metadata.
3. **Given** a client requests JSON enrichment with `geo`, **When** local
   geographic data is available, **Then** it receives the documented minimal or
   full location and ISP data; when it is unavailable, the base response remains
   successful and exposes the documented null or warning behavior.
4. **Given** a client sends conflicting IP filters or requests `geo` without
   JSON, **When** the request is processed, **Then** it receives the documented
   400 response.

---

### User Story 2 - Generate a UUID (Priority: P1)

As a client application, I can generate UUID version 4 or 7 through the
existing endpoint, so that I can retain a simple dependency-free integration.

**Why this priority**: `getuuid` is a current public API with a small, stable
contract that must survive the rebuild.

**Independent Test**: Request `/getuuid` with no version, version 4, version 7,
an invalid version, and a non-GET method; verify documented status and payloads.

**Acceptance Scenarios**:

1. **Given** a client requests `GET /getuuid` without a version, **When** the
   request succeeds, **Then** it receives a valid UUID v4.
2. **Given** a client requests version 4 or 7, **When** the request succeeds,
   **Then** it receives a valid UUID of that version and the corresponding
   version value.
3. **Given** a client requests an unsupported version or non-GET method,
   **When** the request is processed, **Then** it receives the documented 400 or
   405 response without internal details.

---

### User Story 3 - Discover and integrate the APIs (Priority: P2)

As a developer, I can browse a documentation hub and interactive API reference,
so that I can discover both endpoints and make valid requests without reading
the source code.

**Why this priority**: discoverability and Swagger documentation are part of the
current product experience.

**Independent Test**: Open the documentation hub and each interactive reference
and verify that both endpoints, parameters, and documented responses are
available and match the deployed behavior.

**Acceptance Scenarios**:

1. **Given** a developer opens `/docs` or `/api/documentation`, **When** the
   page loads, **Then** it lists Get IP and Get UUID with links to their API
   references.
2. **Given** a developer opens the Get IP or Get UUID API reference, **When** it
   loads, **Then** it presents the current paths, methods, parameters, success
   responses, error responses, and rate-limit behavior.

### Edge Cases

- `GET /getip` with both `ipv4` and `ipv6` returns the documented client error.
- A requested IP version that does not match the detected address returns the
  documented not-found response.
- Private, reserved, missing, or failed geographic lookups do not fail `getip`.
- `geo` is rejected outside JSON mode and accepts the documented disabled,
  minimal, and full forms.
- `GET /getuuid` rejects nonnumeric and unsupported versions.
- `OPTIONS` succeeds without a response body; unsupported methods return the
  documented 405 response.
- A client exceeding the per-IP request allowance receives 429 without an
  internal error payload.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST provide `GET /getip` with the existing text and
  JSON response formats, parameter behavior, status codes, fields, and error
  semantics documented in `apis/getip/README.md`.
- **FR-002**: The system MUST detect the client IP using the existing trusted
  proxy-header behavior and identify its IP version.
- **FR-003**: The system MUST support the existing `getip` IPv4/IPv6 filters and
  optional JSON-only geographic minimal and full enrichment behavior.
- **FR-004**: The system MUST keep `getip` usable when geographic data is absent,
  unavailable, or cannot resolve an address.
- **FR-005**: The system MUST provide `GET /getuuid` with UUID v4 as default and
  support for v4 and v7, preserving documented payloads and invalid-input
  behavior.
- **FR-006**: The system MUST provide the existing documentation hub paths and
  interactive Swagger/OpenAPI references for Get IP and Get UUID.
- **FR-007**: The published API reference MUST match deployed request and
  response behavior, including errors and rate limits.
- **FR-008**: The system MUST retain per-IP rate limiting, allow `OPTIONS`,
  reject unsupported methods, validate public input, and preserve the documented
  security and no-cache headers for UUID responses.
- **FR-009**: The system MUST return stable, machine-readable error responses
  without stack traces, configuration values, or other internal details.
- **FR-010**: The system MUST provide structured request logs and basic metrics
  sufficient to observe request volume, response status, latency, rate limiting,
  and geographic lookup degradation.

### Non-Functional Requirements

- **NFR-001**: The rebuild MUST preserve the existing public HTTP contracts;
  incompatible changes require a documented, versioned migration.
- **NFR-002**: Under 100 concurrent valid requests to either base endpoint, at
  least 95% of responses MUST complete within 200 ms and at least 99% MUST
  complete within 500 ms, excluding client-network time.
- **NFR-003**: Under the same load, the service MUST sustain at least 100
  successful base-endpoint requests per second with an application error rate
  below 1%.
- **NFR-004**: Geographic enrichment MUST use local data at request time and
  MUST NOT introduce a per-request remote dependency.
- **NFR-005**: Public documentation MUST be usable with keyboard navigation and
  must present readable request and response examples.

### Key Entities *(include if feature involves data)*

- **API request**: A client call identified by endpoint, method, query
  parameters, detected IP, response status, and latency.
- **Get IP response**: The detected address plus optional JSON metadata,
  geographic location, ISP information, and lookup warnings.
- **UUID response**: A generated UUID and its version.
- **API contract**: The published endpoint path, method, parameters, response
  schemas, status codes, headers, and rate-limit behavior.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of documented successful and error scenarios for `getip` and
  `getuuid` pass contract tests against the rebuilt service.
- **SC-002**: At least 95% of valid base-endpoint requests complete within
  200 ms under 100 concurrent requests, and at least 99% complete within
  500 ms.
- **SC-003**: The service sustains at least 100 successful base-endpoint
  requests per second under the defined load with fewer than 1% application
  errors.
- **SC-004**: Both public endpoints are discoverable from the documentation hub,
  and their interactive API references expose all documented parameters and
  responses.
- **SC-005**: When geographic data is unavailable, 100% of sampled `getip`
  JSON requests still return the base IP response and the documented degradation
  indication.

## Assumptions

- The existing Laravel implementation and documentation are the source of truth
  for compatibility during this rebuild.
- The new programming language and runtime will be selected during planning;
  this specification intentionally does not choose one.
- Existing endpoint paths, public hostname, no-authentication model, and
  configurable default rate limit of 60 requests per IP per minute remain in
  scope.
- GeoLite2 City and ASN data remains optional, is provisioned outside source
  control, and is refreshed operationally.
- New endpoints, authentication, persistent user data, and a client dashboard
  are out of scope for this rebuild.
