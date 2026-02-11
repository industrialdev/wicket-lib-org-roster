# Membership Cycle Strategy: Roster Specification

## 1) Scope and Intent
- Implement a new roster strategy for ESCRS National Society rosters where roster management is bound to `organization_membership` (membership cycle), not only `organization`.
- Preserve existing behavior for `direct`, `cascade`, and `groups` strategies.
- Keep production-safe rollout: opt-in only, no default behavior changes.
- Strategy key constant for this spec: `membership_cycle`.

## 2) Source Documents Analyzed
- `docs/membership-cycle-logic.md`
- `docs/membership-cycle-card-display.md`
- `docs/membership-cycle-add-remove.md`
- `docs/membership-cycle-bulk-upload.md`
- `docs/membership-cycle-seats-assigment.md`
- `docs/membership-cycle-roles-permissions.md`

## 3) Requirement Consolidation

### 3.1 Core Model
- Roster ownership key: `organization_membership_uuid`.
- Each membership record has an independent roster and independent seat counters.
- Multiple concurrent roster records per organization are expected and valid.

### 3.2 Membership Types and Cycles
- Supported membership labels for this strategy:
  - `ESCRS Membership National Society Full`
  - `ESCRS Membership National Society Trainee`
- Durations: 1-year and 3-year.
- Visibility can be high by design:
  - Normal active year: up to 8 active rosters.
  - Renewal period: up to 12 visible (8 active + up to 4 renewed).
  - First migration year: up to 16 visible (legacy non-December variants included).

### 3.3 Roster Screens
- Two cycle screens are required:
  - Active year screen.
  - Following/upcoming year screen.
- UI must not assume fixed roster count; render dynamic roster cards/tables.
- Each roster UI block must clearly show:
  - Tier (Full/Trainee)
  - Duration (1-year/3-year)
  - Start year
  - Membership status (Active/Delayed)

### 3.4 Add / Remove
- Add fields: first name, last name, email.
- Remove behavior: end-date membership assignment on removal date; do not delete person profile.
- Cannot remove membership owner.
- Duplicate add in same active membership cycle must be blocked.

### 3.5 Bulk Upload
- CSV columns: first name, last name, email, membership type.
- Membership type whitelist (strict): only the two ESCRS values above.
- Invalid membership values must skip/reject row (no partial coercion).
- Bulk upload only adds records; no deletions.

### 3.6 Seats and Checkout
- Seat capacity validation must be cycle-specific (`organization_membership_uuid` scope).
- “Purchase Additional Seats” CTA must carry membership UUID for the selected cycle.
- Checkout completion must increment seats only for that cycle.
- UI must display max-seat alert and purchase path when full.

### 3.7 Search and Pagination
- Search by name and email.
- Pagination required for long lists.

## 4) Contradictions and Resolutions

### 4.1 Role Scope Conflict
- Conflict:
  - `membership-cycle-add-remove.md` says only `Membership_Manager` can add/remove.
  - `membership-cycle-roles-permissions.md` and `membership-cycle-logic.md` include both owner and manager for management.
- Resolution:
  - Strategy-level policy must be config-driven.
  - Default Membership Cycle strategy mutating actions (`add/remove/bulk`) to `membership_manager` only.
  - Viewing can remain `membership_owner` + `membership_manager`.
  - Backward compatibility preserved by containing this rule inside the Membership Cycle strategy config namespace.

### 4.2 “Delayed” Status Semantics
- Docs require “Delayed” visibility but do not define canonical source field.
- Resolution:
  - Add explicit status mapping layer in strategy config:
    - `active`
    - `delayed`
    - `upcoming`
  - Do not hardcode assumptions in templates.

### 4.3 “Two Screens” vs “Many Rosters”
- “Two roster screens” is a cycle-level partition, not two total rosters.
- Resolution:
  - Two tabs/views, each containing N rosters.
  - Roster list count remains dynamic.

## 5) Backward Compatibility Contract (Non-Negotiable)
- Existing strategies unchanged by default.
- New strategy introduced as opt-in (`roster.strategy = membership_cycle`).
- Existing endpoints and payloads keep current behavior when strategy is not `membership_cycle`.
- Existing config keys remain valid; strategy-specific keys are additive only.
- Existing templates continue to render for current strategies.

## 6) Proposed Technical Design

### 6.1 Strategy Wiring
- Add new strategy class implementing `RosterManagementStrategy`.
- Register strategy in `MemberService::init_strategies()`.
- Keep fallback behavior unchanged.

### 6.2 Membership Cycle Resolver
- Add service-level resolver to list organization memberships with normalized metadata:
  - `organization_membership_uuid`
  - membership label
  - tier (`full|trainee`)
  - duration (`1|3`)
  - start year
  - end year
  - status (`active|delayed|upcoming`)
  - seat metrics
- Resolver output drives tabs/cards and import target selection.

### 6.3 Add/Remove Path
- Membership Cycle strategy add/remove must require explicit `membership_uuid` in context.
- No implicit `getMembershipForOrganization()` fallback for Membership Cycle strategy mutations.
- Duplicate check must be cycle-scoped and active-membership scoped.

### 6.4 Bulk Import Path
- Dedicated process endpoint or service method for Membership Cycle strategy bulk import.
- Validate CSV schema + strict membership whitelist.
- Import report output per row: added / skipped / duplicate / invalid-membership / seat-full / error.

### 6.5 Seat Purchase Path
- Confirm `AdditionalSeatsService` and checkout hooks consume membership UUID from request metadata.
- Ensure order completion writes to targeted `organization_membership_uuid` only.
- Preserve current additional seats behavior for non-membership-cycle flows.

### 6.6 UI Rendering
- Extend members view for cycle tabs and roster cards.
- Reuse unified list patterns (search/pagination), adding cycle context controls.
- Keep Datastar interactions and SSE response style.

## 7) Security and Validation
- Capability checks must remain centralized via `PermissionHelper`/`PermissionService`.
- All mutating requests require nonce + org membership context validation.
- Validate that actor can manage target cycle roster for target organization.
- Reject cross-cycle or cross-org tampering attempts.
- Sanitize CSV and all request fields; fail closed on invalid enum values.

## 8) Test Strategy (Pest)
- Add unit tests for:
  - Strategy selection and fallback safety.
  - Membership cycle resolver normalization.
  - Role gate matrix (view vs add/remove/bulk).
  - Duplicate detection scoped to membership UUID.
  - Seat validation scoped to membership UUID.
- Add integration-style tests for process handlers:
  - Add/remove with explicit membership UUID.
  - Bulk upload whitelist behavior and row rejection.
  - Seat purchase metadata propagation and cycle-targeted increment.
- Add regression tests to prove existing strategies unchanged.

## 9) Acceptance Criteria (Implementation Complete)
- Membership Cycle strategy can be enabled without affecting existing strategy behavior.
- Two-cycle UI works with dynamic roster counts and required metadata display.
- Add/remove/bulk operate strictly per membership cycle.
- Seat limits and purchases are cycle-accurate.
- No auto-carryover across cycles.
- Test suite covers new logic and key regressions.

## 10) Config Schema Reference
- Proposed additive configuration schema is documented in `docs/membership-cycle-config-schema.md`.
- Implementation must keep existing defaults unchanged and apply new keys only when `roster.strategy = membership_cycle`.
