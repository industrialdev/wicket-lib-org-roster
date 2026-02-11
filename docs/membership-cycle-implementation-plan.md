# Membership Cycle Strategy: Implementation Plan (Backward-Compatible)

## Objective
Deliver ESCRS membership-cycle roster management as a new strategy without changing behavior of existing production strategies.

## Guiding Rules
- Opt-in only; no default strategy switch.
- Additive config and endpoints; no breaking payload changes.
- Test-first on high-risk paths (seat assignment, add/remove, imports).

## Phase 0 — Discovery and Guardrails
- [ ] Confirm strategy key name is `membership_cycle` (fixed).
- [ ] Confirm canonical source fields for membership status mapping (`active`, `delayed`, `upcoming`).
- [ ] Confirm exact ESCRS membership labels/identifiers to whitelist for bulk upload.
- [ ] Confirm whether owner can mutate in Membership Cycle strategy (default planned: manager-only for add/remove/bulk).

Exit Criteria:
- [ ] Ambiguities resolved and frozen in config defaults.

## Phase 1 — Strategy Scaffolding
- [ ] Add `MembershipCycleStrategy` implementing `RosterManagementStrategy`.
- [ ] Register strategy in `MemberService::init_strategies()`.
- [ ] Keep fallback unchanged (`cascade`) for unknown strategy keys.
- [ ] Add strategy-specific config block in `src/config/config.php` (additive only).

Exit Criteria:
- [ ] Existing strategies still selected and functioning exactly as before.

## Phase 2 — Membership Cycle Resolver
- [ ] Add resolver service/method for organization membership cycles with normalized metadata.
- [ ] Support dynamic roster cardinality (8/12/16+ as data requires).
- [ ] Expose cycle partitions (active-year vs following-year) for UI consumption.

Exit Criteria:
- [ ] Resolver returns deterministic, template-ready data for all expected cycle permutations.

## Phase 3 — Add/Remove by Membership Cycle
- [ ] Require explicit `membership_uuid` for Membership Cycle strategy mutating actions.
- [ ] Enforce role gate per Membership Cycle strategy config (default: membership_manager for mutating actions).
- [ ] Implement duplicate prevention scoped to selected membership cycle and active records.
- [ ] Ensure removal end-dates person-membership for the selected cycle only.
- [ ] Prevent owner removal.

Exit Criteria:
- [ ] Add/remove behavior matches Membership Cycle strategy docs and does not alter non-membership-cycle flows.

## Phase 4 — Bulk Upload
- [ ] Add bulk upload endpoint/service path for Membership Cycle strategy.
- [ ] Enforce strict CSV schema and whitelist values.
- [ ] Skip/reject invalid rows with row-level reporting.
- [ ] Apply seat-capacity checks per membership cycle.
- [ ] Ensure import is additive only (no delete operations).

Exit Criteria:
- [ ] Bulk import result report is deterministic and auditable.

## Phase 5 — Seat Purchase Integration
- [ ] Verify purchase URL includes target `membership_uuid`.
- [ ] Ensure checkout processing increments seats on target membership cycle only.
- [ ] Ensure UI refresh reflects updated seats after checkout.

Exit Criteria:
- [ ] Mid-cycle seat purchases affect only intended cycle roster.

## Phase 6 — UI Delivery
- [ ] Add cycle-level switch/tabs (active year / following year).
- [ ] Render roster cards/list dynamically for each cycle set.
- [ ] Include required display fields: name, nominal, email, membership, status.
- [ ] Reuse existing unified search + pagination behavior.
- [ ] Keep existing strategy UIs unchanged.

Exit Criteria:
- [ ] Membership Cycle strategy UI satisfies card display and navigation requirements.

## Phase 7 — Test Coverage and Regression Safety
- [ ] Add Pest unit tests for strategy selection and compatibility.
- [ ] Add resolver tests for cycle/status normalization.
- [ ] Add add/remove tests for role gating, duplicates, owner protection, cycle-scoped end-date.
- [ ] Add bulk upload tests for whitelist + invalid-row handling + seat limits.
- [ ] Add seat purchase tests for membership-scoped updates.
- [ ] Add regression tests to assert unchanged behavior for `direct`, `cascade`, `groups`.

Exit Criteria:
- [ ] `composer test` passes with new and existing tests.

## Phase 8 — Release Controls
- [ ] Feature flag Membership Cycle strategy rollout (strategy config + environment gating if needed).
- [ ] Add observability logs for add/remove/import/seat operations keyed by membership UUID.
- [ ] Document rollback path (switch strategy key back, no schema rollback required).

Exit Criteria:
- [ ] Production rollout has monitored enable/disable path with low blast radius.

## Risk Register and Mitigations
- Risk: cross-cycle seat mutation.
  - Mitigation: mandatory membership UUID propagation + dedicated tests.
- Risk: accidental behavior drift in existing strategies.
  - Mitigation: regression test matrix + additive-only code paths.
- Risk: ambiguous delayed/upcoming state mapping.
  - Mitigation: explicit config mapping and resolver normalization.
- Risk: role-policy mismatch across docs.
  - Mitigation: strategy-local permission config defaults + final stakeholder sign-off.

## Definition of Done
- [ ] Membership Cycle strategy exists as opt-in and is production-safe.
- [ ] All acceptance criteria in `docs/membership-cycle-specification.md` are satisfied.
- [ ] Test suite passes and includes regression protection.
- [ ] Documentation updated for operations and rollout.
