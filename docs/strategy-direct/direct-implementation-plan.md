# Direct Strategy: Implementation and Hardening Plan

## Objective
Maintain direct strategy as stable default while improving safety and documentation clarity.

## Guiding Rules
- No behavioral drift for other strategies.
- Keep direct behavior explicit and deterministic.
- Prioritize regression coverage for membership targeting and role mutations.

## Phase 0 — Baseline Contracts
- [x] Confirm `direct` is default strategy key.
- [x] Confirm membership resolution precedence (explicit UUID then org fallback).
- [x] Confirm remove contract requires `person_membership_id`.
- [x] Confirm owner-protection/owner-assignment guards are config-driven.

Exit Criteria:
- [x] Direct contracts are documented and testable.

## Phase 1 — Membership Target Safety
- [x] Support explicit `membership_uuid` and `membership_id` overrides.
- [x] Validate explicit membership-org scope.
- [x] Keep org fallback resolver for backward compatibility.
- [ ] Consider stricter warning/telemetry when fallback is used in multi-membership orgs.

Exit Criteria:
- [x] Membership targeting behavior is deterministic and documented.

## Phase 2 — Add/Remove Reliability
- [x] Ensure add flow remains: person -> connection -> seat -> roles.
- [x] Keep email failure non-blocking with logs.
- [x] Enforce owner removal protection when configured.
- [x] Keep direct remove strategy role-only by design.

Exit Criteria:
- [x] Add/remove side-effects are stable and explicit.

## Phase 3 — Validation and UX Guards
- [x] Nonce and permission checks happen before strategy execution.
- [x] Duplicate check exists for membership-scoped add flow in handler.
- [ ] Evaluate making duplicate guard strategy-local for stronger encapsulation.

Exit Criteria:
- [x] Existing guard rails protect common mutation paths.

## Phase 4 — Tests and Regression Safety
- [x] Keep tests for explicit membership resolution success.
- [x] Keep tests for cross-org mismatch rejection.
- [x] Keep tests for org fallback membership resolution.
- [x] Keep cross-strategy wiring/regression suite green.

Exit Criteria:
- [x] `composer test` passes with direct strategy coverage intact.

## Risk Register
- Risk: wrong membership selected when explicit context is omitted in multi-membership orgs.
  - Mitigation: explicit membership UUID support and operational guidance.
- Risk: remove-path confusion between strategy and process handler side-effects.
  - Mitigation: document separation clearly and avoid hidden cascade behavior in direct strategy.
- Risk: duplicate prevention drift across handlers/strategies.
  - Mitigation: keep tests around membership-scoped duplicate checks.

## Definition of Done
- [x] Direct strategy contracts are clearly documented.
- [x] Membership targeting and remove semantics are covered.
- [x] Regression safety remains intact.
