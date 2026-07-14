# HTTP Contract: Rebuild Public APIs in Go

This document defines the compatibility surface. The existing endpoint READMEs and generated OpenAPI documents remain the detailed baseline until checked-in Go OpenAPI JSON documents replace them where applicable.

## Common behavior

- Base URL remains `https://api.galarca.dev`; the container listens on port 8080.
- `GET` and `OPTIONS` are accepted. `OPTIONS` returns 204; other methods return 405 with the existing endpoint-specific error payload.
- CORS permits `GET, OPTIONS`; responses include `X-Content-Type-Options: nosniff` and `X-Robots-Tag: noindex`.
- Per-IP rate limits return 429. Defaults are 60 requests per 60 seconds and remain configurable.
- Error bodies never expose internal errors or configuration.

## `GET /getip`

| Input | Contract |
|---|---|
| no parameters | Plain text detected IP, 200. |
| `format=json` | JSON with `response_code`, `ip`, `version`, `private`, and `meta`. |
| `ipv4` or `ipv6` | Requires the corresponding detected address version; otherwise 404. |
| both IP filters | 400 with the documented conflict error. |
| `geo` with JSON | Minimal lookup for truthy/minimal values; full lookup for `full`; disabled for false values. |
| `geo` without JSON | 400 plain-text error. |

JSON success metadata includes `api`, `api_version`, ISO-8601 `timestamp`, and `server_timezone`. When geo is requested, `geo.location` and `geo.isp` may be null; `meta.geo_warnings` appears only for documented local-data degradation.

## `GET /getuuid`

| Input | Contract |
|---|---|
| no `version` | JSON UUID v4 and `version: 4`, 200. |
| `version=4` | JSON UUID v4 and `version: 4`, 200. |
| `version=7` | JSON UUID v7 and `version: 7`, 200. |
| any other value | JSON `{ "error", "status": 400 }`, 400. |

Successful and error responses preserve `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`, `Pragma: no-cache`, `Expires: 0`, `Referrer-Policy`, `X-Frame-Options`, and the documented CSP header.

## Documentation routes

| Path | Response |
|---|---|
| `/docs` and `/api/documentation` | Documentation hub with links to both APIs. |
| `/docs/getip` and `/docs/getuuid` | OpenAPI JSON for the respective API. |
| `/api/documentation/getip` and `/api/documentation/getuuid` | Interactive Swagger UI for the respective OpenAPI document. |

The implementation MUST test that the OpenAPI files parse, expose the expected path, and remain aligned with endpoint contract tests.

## Migration verification

The Go implementation preserves the documented Laravel contracts. No incompatible or versioned migration was approved. Contract tests cover text/JSON errors, methods, rate limits, security headers, GeoLite2 degradation, OpenAPI paths, and Swagger assets.
