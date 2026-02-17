# Groups Strategy: Implementation and Hardening Plan

## Objective
Stabilize and document groups-based roster management without changing behavior of production strategies outside `groups`.

## Guiding Rules
- Strategy-scoped changes only.
- Config-driven behavior, no hardcoded client assumptions.
- Regression-safe updates with explicit tests.

## Phase 0 — Contracts and Data Mapping
- [x] Confirm strategy key is `groups`.
- [x] Confirm roster-tag filter contract (`groups.tag_name`, case sensitivity toggle).
- [x] Confirm org-association mapping (`groups.additional_info`).
- [x] Confirm managing role list includes requested delegate variants.

Exit Criteria:
- [x] Eligibility contract is clear and documented.

## Phase 1 — Eligibility and List Reliability
- [x] Resolve manageable groups from active memberships and managing roles.
- [x] Enforce roster tag filter.
- [x] Add fallback tag fetch for payloads missing included tags (`/groups/{id}`).
- [ ] Define and enforce deterministic group sort order (if business requires ordering).

Exit Criteria:
- [x] Eligible users consistently see manageable groups.

## Phase 2 — Add/Remove Safety
- [x] Enforce `group_uuid` requirement.
- [x] Enforce roster-role whitelist.
- [x] Enforce group manage access before mutation.
- [x] Persist org association metadata on add.
- [x] Support configurable removal mode (`end_date`/`delete`).

Exit Criteria:
- [x] Mutations are group-scoped and org-association-safe.

## Phase 3 — Seat-Limit Hardening
- [x] Enforce seat-limited-role guard for configured roles.
- [ ] Remove page-window limitation for very large groups (full-scan or dedicated query).
- [ ] Add targeted tests for >50 member seat-limit scenarios.

Exit Criteria:
- [ ] Seat-limited-role enforcement is complete for high-cardinality groups.

## Phase 4 — UI and Labels
- [x] Groups strategy list and member templates wired.
- [x] Use unified list/view defaults in groups mode.
- [x] Render heading as `Manage Groups` in groups mode.
- [ ] Add explicit UX copy for no-manageable-groups state if product requires distinct message.

Exit Criteria:
- [x] Strategy-specific label and routing behavior are correct.

## Phase 5 — Test and Regression Safety
- [x] Add unit tests for groups eligibility role variants (`delegate`, etc.).
- [x] Add unit tests for tag fallback behavior.
- [x] Keep cross-strategy regression tests green.

Exit Criteria:
- [x] `composer test` passes with groups changes.

## Risk Register
- Risk: payload variance (`tags` absent in included group data).
  - Mitigation: detail-endpoint fallback and test coverage.
- Risk: role slug drift across tenants.
  - Mitigation: config-driven `manage_roles`; avoid hardcoded names in logic.
- Risk: large group seat checks missing conflicts beyond current page window.
  - Mitigation: follow-up full-scan seat validation path.

## Definition of Done
- [x] Groups eligibility matches documented role/tag/org contracts.
- [x] Groups mode heading is strategy-correct.
- [x] Tests cover role and tag-fallback regressions.
- [ ] Seat-limit full-scan hardening completed (if required by scale).
