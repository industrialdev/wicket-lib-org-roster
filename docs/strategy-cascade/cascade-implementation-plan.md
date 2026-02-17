# Cascade Strategy: Implementation and Hardening Plan

## Objective
Keep cascade strategy production-safe for legacy deployments while documenting and hardening known contracts.

## Guiding Rules
- Preserve legacy-compatible behavior.
- Keep strategy boundaries explicit to avoid side-effect drift.
- Add coverage around high-risk mutation paths.

## Phase 0 — Baseline Contracts
- [x] Confirm strategy key is `cascade`.
- [x] Confirm add flow includes connection + membership seat + roles.
- [x] Confirm remove flow requires `person_membership_id` and ends membership.
- [x] Confirm owner removal is blocked in strategy remove flow.

Exit Criteria:
- [x] Cascade contracts are documented and testable.

## Phase 1 — Dependency Reliability
- [x] Verify required helper dependency checks for assignment paths.
- [ ] Expand explicit dependency checks if additional legacy helpers become mandatory.
- [ ] Improve operator-facing error messages for missing helper functions.

Exit Criteria:
- [x] Missing dependency failures are explicit and observable.

## Phase 2 — Role and Relationship Rules
- [x] Keep base/auto role assignment in add path.
- [x] Keep relationship-based role mapping optional and config-driven.
- [x] Keep owner-assignment prevention config behavior.
- [ ] Add focused tests for relationship-mapping permutations.

Exit Criteria:
- [x] Role behavior remains stable and configurable.

## Phase 3 — Remove Flow Safety
- [x] Require `person_membership_id` for remove.
- [x] End-date person membership in cascade remove.
- [x] Remove org-scoped roles on successful membership end-date.
- [ ] Add explicit telemetry for partial failures in mixed side-effects.

Exit Criteria:
- [x] Remove semantics are deterministic.

## Phase 4 — Tests and Regression Safety
- [x] Keep strategy wiring regression tests green.
- [ ] Add dedicated cascade strategy unit tests for add/remove contracts.
- [ ] Add explicit owner-protection tests under cascade strategy.
- [x] Maintain cross-strategy regression safety (`direct`, `groups`, `membership_cycle`).

Exit Criteria:
- [x] `composer test` remains green with cascade-safe behavior.

## Risk Register
- Risk: legacy helper behavior varies by environment.
  - Mitigation: explicit checks + logged failures.
- Risk: relationship-role mapping misconfiguration assigns unintended roles.
  - Mitigation: keep mapping config explicit and test common mappings.
- Risk: remove operation partial success without clear operator visibility.
  - Mitigation: add stronger telemetry around end-date and role-removal steps.

## Definition of Done
- [x] Cascade strategy behavior is clearly documented.
- [x] Remove semantics and owner protection are explicit.
- [x] Cross-strategy stability is preserved.
