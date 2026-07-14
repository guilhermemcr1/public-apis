# Data Model: Rebuild Public APIs in Go

The service is stateless. These are request-time values, configuration, and read-only reference data; no database schema or migration is required.

## API request

| Field | Description | Rules |
|---|---|---|
| endpoint | Requested public path | One of the documented routes. |
| method | HTTP method | GET and OPTIONS accepted; other methods return 405. |
| client_ip | Effective client address | Taken from remote peer unless a trusted proxy supplied a valid header. |
| query | Parsed request parameters | `getip` and `getuuid` rules follow the contract. |
| latency_ms | Completed request latency | Recorded in structured logs and metrics. |
| status | HTTP response status | Recorded in structured logs and metrics. |

## Get IP response

| Field | Description | Rules |
|---|---|---|
| ip | Detected client IP | Valid IPv4 or IPv6; fallback behavior matches the current contract. |
| version | IP version | `v4` or `v6` in JSON responses. |
| private | Address classification | True for private or reserved addresses. |
| meta | Service metadata | JSON responses include API name, version, timestamp, and server timezone. |
| geo.location | Optional geographic result | Null when unavailable; minimal/full shape depends on `geo`. |
| geo.isp | Optional ASN result | Null when unavailable. |
| meta.geo_warnings | Optional degradation signals | Present only when a local lookup failed or data is unavailable. |

## UUID response

| Field | Description | Rules |
|---|---|---|
| uuid | Generated identifier | Canonical UUID string, v4 by default or v7 when requested. |
| version | UUID version | Integer `4` or `7`. |

## Rate-limit entry

| Field | Description | Rules |
|---|---|---|
| client_ip | Limit key | Effective client IP. |
| window_start | Start of the current 60-second window | Replaced after expiry. |
| count | Requests in the window | Reject request 61+ by default. |

Expired entries are removed during normal limiter activity. The limit is local to one process; it is intentionally not a distributed data model.

## Runtime configuration

| Setting | Default | Purpose |
|---|---|---|
| PORT | 8080 | HTTP listener port. |
| APP_TIMEZONE | America/Sao_Paulo | Timestamp and reported server timezone. |
| GETIP_RATE_LIMIT / GETUUID_RATE_LIMIT | 60 | Per-IP allowance per window. |
| RATE_WINDOW_SECONDS | 60 | Limiter window duration. |
| TRUSTED_PROXY_CIDRS | empty | Peers allowed to supply client-IP headers. |
| GEOIP_CITY_DATABASE_PATH | `/data/geoip/GeoLite2-City.mmdb` | Optional City file. |
| GEOIP_ASN_DATABASE_PATH | `/data/geoip/GeoLite2-ASN.mmdb` | Optional ASN file. |
