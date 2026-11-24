# Implementation Plan: System Hardening & Polish (6 improvements)

## Overview

Six improvements requested after the travel-order document preview work:
stalled-approval monitoring + designation impact preview, safer seeded credentials,
frontend tests, itinerary preview tab, PHPStan cleanup + integrity command, and an
admin runbook. Each is implemented as an independent vertical slice with tests and
verification after the slice.

## Assumptions

- Laravel 13 / PHP 8.5, Pest, Inertia React 19, `app/Services` + `app/Repositories` architecture.
- Workflow authorization is delivery-based; do not change routing/approval semantics.
- Do not split Budget/Mayor/HRMO portals; `/approvals` stays shared.
- `travel-orders.reassign` remains admin-only; rescue never advances the step.
- Password hardening is additive: no existing account is locked out, and local seed data
  still works for development.

## Task List

### Phase 1 — Operations

- [x] Task 1: Stalled-approval admin widget (age + holder) + designation impact preview/bulk reassign.
- [x] Checkpoint 1: focused tests, full suite, build, lint.

### Phase 2 — Security

- [x] Task 2: Force password change flag + env-driven seed credentials (non-local).
- [x] Checkpoint 2: auth tests, full suite.

### Phase 3 — UX & tests

- [x] Task 3: Print preview tabs (Travel Order / Itinerary) in approval view.
- [x] Task 4: Vitest + Testing Library with tests for portal switcher, reassign dialog, approval actions.
- [x] Checkpoint 3: `npm run check`, `npm run types:check`, `npm test`, build.

### Phase 4 — Hygiene & docs

- [x] Task 5: PHPStan pre-existing errors cleanup + `travel-orders:check-integrity` artisan command.
- [x] Task 6: Admin runbook in `CODEBASE_GUIDE.md`.
- [x] Checkpoint 4: full gates, browser QA, commit.

## Risks and Mitigations

| Risk                                              | Impact | Mitigation                                                               |
| ------------------------------------------------- | ------ | ------------------------------------------------------------------------ |
| New DB columns break test DB                      | High   | Migration only, no data backfill assumptions; run migrate:fresh in tests |
| Force password change blocks seeded demo logins   | High   | Flag defaults off; only set via explicit seeder/env for non-local        |
| PHPStan fixes change behavior                     | Medium | Runtime-safe guards, no suppressions, tests around touched code          |
| Frontend test runner conflicts with Vite 7/Vitest | Medium | Pin compatible versions; run check after install                         |

## Open Questions

- None blocking; username `marible` vs `maribel` left as-is (not part of this scope).
