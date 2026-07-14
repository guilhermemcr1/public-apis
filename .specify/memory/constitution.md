<!--
Sync Impact Report
- Version change: unversioned template -> 1.0.0
- Modified principles: none (initial adoption)
- Added sections: Performance and Reliability; Development Workflow and Quality Gates
- Removed sections: none
- Templates requiring updates:
  - ✅ .specify/templates/plan-template.md
  - ✅ .specify/templates/spec-template.md
  - ✅ .specify/templates/tasks-template.md
- Follow-up TODOs: ratification date is unknown.
-->
# APIs Públicas Constitution

## Core Principles

### I. Contract Compatibility First
The replacement implementation MUST preserve the documented HTTP contracts for
existing public endpoints unless a versioned, documented migration is approved.
This includes paths, methods, parameters, status codes, response fields, and
error semantics. Contract tests MUST cover every changed public endpoint.

Rationale: clients depend on these APIs without coordination, so compatible
behavior is the smallest safe migration.

### II. Testable Behavior Is Mandatory
Every behavior change MUST include automated tests at the narrowest useful
level. Public endpoint changes MUST include integration or contract tests;
validation, parsing, and generation rules MUST include focused unit tests.
The full test suite MUST pass before merge.

Rationale: the rewrite must prove behavioral equivalence instead of assuming it.

### III. Fast by Design
Each endpoint MUST define and verify a p95 latency target, throughput target,
and resource ceiling before implementation. Request paths MUST avoid remote
lookups and unbounded work where local data or bounded processing is possible.
Performance claims MUST be validated with a repeatable benchmark or load test.

Rationale: public APIs are only useful when they remain responsive under load.

### IV. Clear and Accessible API Experience
Every public endpoint MUST have concise documentation with request examples,
success and error responses, parameter rules, and rate-limit behavior. Errors
MUST be machine-readable, stable, and actionable. Interactive documentation,
when provided, MUST reflect the deployed contract.

Rationale: a small API succeeds when clients can integrate correctly without
source-code knowledge or support requests.

### V. Operationally Safe Defaults
All public inputs MUST be validated at the boundary. Endpoints MUST enforce
rate limits, return no sensitive configuration or internal errors, and emit
structured logs and basic request metrics. Optional dependencies, such as
GeoLite databases, MUST fail gracefully without breaking the base endpoint.

Rationale: public exposure needs predictable failure modes and enough signals
to diagnose production behavior.

## Performance and Reliability

The implementation plan MUST state measurable latency, throughput, concurrency,
availability, and memory targets for each endpoint class. The project MUST
document its deployment runtime, horizontal-scaling approach, timeouts, and
rate-limit policy. Cache only when measurement shows it is needed; cache keys,
TTL, and invalidation behavior MUST then be documented.

## Development Workflow and Quality Gates

Before implementation, each feature plan MUST pass the Constitution Check:
contract impact identified; tests defined; performance targets measurable;
documentation impact identified; and security, rate-limit, observability, and
graceful-degradation behavior addressed. Before merge, run formatting, static
analysis where supported, the full automated test suite, and the relevant
benchmark or load test when a request path changes.

## Governance

This constitution supersedes conflicting project guidance. A proposed amendment
MUST document its rationale, affected templates, migration impact, and semantic
version bump. MAJOR versions remove or redefine principles incompatibly; MINOR
versions add or materially expand principles; PATCH versions clarify wording
without changing obligations. Every plan and review MUST verify compliance; a
violation requires an explicit, time-bounded exception and follow-up task.

**Version**: 1.0.0 | **Ratified**: TODO(RATIFICATION_DATE): original adoption date is unknown | **Last Amended**: 2026-07-13
