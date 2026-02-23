# Supported Roster Strategies

This document is the canonical strategy reference for roster behavior in this library.

Strategy selection uses:
- `roster.strategy` in `\OrgManagement\Config\get_config()`

Current strategy keys:
- `direct`
- `cascade`
- `groups`
- `membership_cycle`

## 1) Strategy Architecture

### Strategy Interface
- Contract: `src/Services/Strategies/RosterManagementStrategy.php`
- Required methods:
  - `add_member($org_id, $member_data, $context = [])`
  - `remove_member($org_id, $person_uuid, $context = [])`

### Strategy Registry
- Service: `src/Services/MemberService.php`
- Strategy map currently includes:
  - `cascade` => `CascadeStrategy`
  - `direct` => `DirectAssignmentStrategy`
  - `groups` => `GroupsStrategy`
  - `membership_cycle` => `MembershipCycleStrategy`

### Selection Behavior
- Active strategy is read from `ConfigService::get_roster_mode()`.
- Unknown strategy keys fall back to `cascade` (backward-safe behavior).

## 2) Cross-Strategy Contracts

### Inputs
- `org_id` / `org_uuid`: organization scope key.
- `member_data`: person payload (name, email, optional profile fields).
- `context`: strategy-specific extra data (for example `group_uuid`, `membership_uuid`, roles).

### Security Baseline
- Nonce validation at process endpoints.
- Capability checks through `PermissionHelper`/`PermissionService`.
- Sanitization on all request fields before strategy execution.

### Cache Behavior
- Member list cache uses membership UUID scoped keys.
- Add/remove handlers clear membership-specific cache after successful mutation.

### Seat Handling
- Seat availability is rendered on member list screens.
- Additional seats flow is integrated through `AdditionalSeatsService`.
- Membership target for seat updates must remain explicit and correct.

## 3) Strategy Matrix (Quick Comparison)

### Scope Key
- `direct`: organization + resolved membership (implicit unless context provides explicit membership UUID).
- `cascade`: organization with cascade-oriented assignment behavior.
- `groups`: `group_uuid` + organization identifier.
- `membership_cycle`: explicit `organization_membership_uuid` (required for mutating actions).

### Primary Roster Unit
- `direct`: org membership assignment in immediate roster flow.
- `cascade`: org relationship with additional side-effects handled by cascade/system behavior.
- `groups`: group membership record.
- `membership_cycle`: membership cycle record.

### Typical Remove Behavior
- `direct`: role removal (without forced membership end-date in strategy method).
- `cascade`: end-date memberships and relationships per cascade expectations.
- `groups`: remove group member record (or configured group removal mode).
- `membership_cycle`: end-date person membership assignment for target cycle only.

### Best Fit
- `direct`: explicit immediate assignment models.
- `cascade`: ecosystems with trusted downstream automation.
- `groups`: roster-by-group organizations.
- `membership_cycle`: multi-cycle/year roster operations with strict cycle boundaries.

## 4) Detailed Strategy Specs

### A) `direct`

#### Class
- `src/Services/Strategies/DirectAssignmentStrategy.php`

#### Core Behavior
- Creates/updates person profile.
- Builds person-to-organization connection if needed.
- Assigns person to membership seat directly in flow.
- Assigns base and additional roles.
- Sends assignment email and logs touchpoint.

#### Membership Resolution
- Default path resolves membership by organization.
- Supports explicit context override:
  - `context['membership_uuid']`
  - `context['membership_id']`
- If explicit membership is supplied, validates membership belongs to the target organization.

#### Add Context Keys
- `relationship_type`
- `relationship_description`
- `roles`
- Optional explicit membership UUID (`membership_uuid` / `membership_id`)

#### Remove Context Keys
- `person_membership_id` required by strategy remove method.

#### Strengths
- Deterministic immediate behavior.
- Lower dependency on external cascade rules.

#### Risks
- If org has multiple concurrent memberships and explicit membership is not passed, resolver choice may not reflect desired cycle.

### B) `cascade`

#### Class
- `src/Services/Strategies/CascadeStrategy.php`

#### Core Behavior
- Creates/updates person and relationship.
- Delegates parts of assignment behavior to legacy/system cascade path.
- Applies role mapping and relationship-driven role logic as configured.

#### Typical Use
- Existing deployments where legacy cascade assumptions are part of business process.

#### Strengths
- Compatible with environments expecting downstream side-effects.

#### Risks
- Behavior may vary by external environment/dependencies.
- Harder to reason about exact side-effects versus direct explicit assignment.

### C) `groups`

#### Class
- `src/Services/Strategies/GroupsStrategy.php`

#### Core Behavior
- Requires `group_uuid`.
- Adds/removes group members with group role constraints.
- Validates manager access via group membership and org identifier mapping.
- Enforces special role restrictions (for example managing roles non-removable).
- Groups landing (`organization-management`) is membership-driven:
  - shows active roster-tagged groups the user belongs to,
  - auto-redirects only when exactly one group row is present,
  - gates `Group Profile` / `Manage Members` actions by `groups.manage_roles`.

#### Key Config Namespace
- `groups`
  - `tag_name`
  - `manage_roles`
  - `roster_roles`
  - `seat_limited_roles`
  - `additional_info`
  - `removal`
  - `ui`

#### Typical Use
- Organizations where roster is fundamentally managed as group memberships.

#### Strengths
- Fine-grained role control at group layer.
- Fits MDP group-driven workflows.

#### Risks
- Depends on accurate org identifier extraction/mapping.
- Access and seat constraints are group-model specific, not membership-cycle model.

### D) `membership_cycle`

#### Class
- `src/Services/Strategies/MembershipCycleStrategy.php`

#### Core Behavior
- Requires explicit membership UUID for mutating operations.
- Validates membership UUID belongs to target organization.
- Add path delegates to direct strategy assignment using explicit cycle.
- Remove path end-dates target person membership assignment for that cycle only.
- Prevents owner removal by default (strategy-local config).
- Uses strategy-specific permission role overrides for add/remove/purchase checks.

#### Activation
- `roster.strategy = membership_cycle`

#### Key Config Namespace
- `membership_cycle`
  - `strategy_key`
  - `permissions`:
    - `add_roles`
    - `remove_roles`
    - `purchase_seats_roles`
    - `prevent_owner_removal`
  - `member_management`:
    - `require_explicit_membership_uuid`

#### UI/Request Expectations
- Membership UUID should be propagated in:
  - members list/search pagination requests
  - add-member form posts
  - remove-member form posts
  - post-success refresh actions

#### Strengths
- Correct for concurrent cycle scenarios.
- Stronger protection against cross-cycle seat/member mutation.

#### Risks
- Any endpoint missing `membership_uuid` propagation can cause blocked actions in this mode.
- Generic CSV bulk upload is available behind `ui.member_list.show_bulk_upload` (default `false`), but ESCRS-specific whitelist and cycle-tab UX are still pending.

## 5) Permission Model by Strategy

### Global Defaults
- `permissions.add_members`
- `permissions.remove_members`
- `permissions.purchase_seats`

### Strategy-Specific Overrides
- When `roster.strategy = membership_cycle`, helper methods support overrides from:
  - `membership_cycle.permissions.add_roles`
  - `membership_cycle.permissions.remove_roles`
  - `membership_cycle.permissions.purchase_seats_roles`

### Expected Outcome
- Existing strategies keep global permission behavior.
- `membership_cycle` can use stricter role defaults without changing global defaults.

## 6) Endpoint and Template Touchpoints

### Process Endpoints
- Add member: `templates-partials/process/add-member.php`
- Remove member: `templates-partials/process/remove-member.php`
- Bulk upload members (CSV): `templates-partials/process/bulk-upload-members.php`
- Group add/remove:
  - `templates-partials/process/add-group-member.php`
  - `templates-partials/process/remove-group-member.php`

### Member Views/Lists
- Unified member view: `templates-partials/members-view-unified.php`
- Unified member list: `templates-partials/members-list-unified.php`
- Legacy member list: `templates-partials/members-list.php`
- Container page: `templates-partials/organization-members.php`

### Critical Parameter
- `membership_uuid` should be preserved through view -> list -> process roundtrips for cycle-scoped behavior.

## 7) Backward Compatibility Rules

- Default strategy remains `direct` unless explicitly changed.
- Existing key semantics in `permissions`, `ui`, `groups`, `additional_seats` remain unchanged.
- New strategy config is additive under `membership_cycle`.
- Strategy-specific behavior only activates when selected.

## 8) Recommended Strategy Selection Guide

- Choose `direct` when:
  - You want explicit immediate assignment in the request path.
  - You have one dominant active membership context per organization.

- Choose `cascade` when:
  - Your platform relies on established downstream cascade logic.
  - Legacy workflows depend on implicit side-effects.

- Choose `groups` when:
  - Roster ownership is group-centric.
  - Access and seat semantics are tied to group roles.

- Choose `membership_cycle` when:
  - Multiple concurrent memberships/cycles are normal.
  - Add/remove/import/seat actions must be scoped to an explicit cycle ID.
  - Renewal workflows require parallel active/upcoming rosters.

## 9) Testing Expectations

- Strategy wiring tests should assert all keys are registered.
- Membership resolution tests should cover:
  - explicit cycle success
  - cross-org mismatch rejection
  - fallback behavior for legacy paths
- Permission tests should validate strategy-local role override behavior.
- Remove behavior tests should validate owner-protection and cycle-scoped end-date behavior.
- Regression tests must continue proving unchanged behavior for `direct`, `cascade`, `groups`.

## 10) Related Documentation

- [Unified Specifications](SPECS.md)
- [Configuration](CONFIGURATION.md)
- [Architecture](ARCHITECTURE.md)
- [Direct Specification](strategy-direct/direct-specification.md)
- [Direct Implementation Plan](strategy-direct/direct-implementation-plan.md)
- [Direct Config Schema](strategy-direct/direct-config-schema.md)
- [Cascade Specification](strategy-cascade/cascade-specification.md)
- [Cascade Implementation Plan](strategy-cascade/cascade-implementation-plan.md)
- [Cascade Config Schema](strategy-cascade/cascade-config-schema.md)
- [Groups Specification](strategy-groups/groups-specification.md)
- [Groups Implementation Plan](strategy-groups/groups-implementation-plan.md)
- [Groups Config Schema](strategy-groups/groups-config-schema.md)
- [Membership Cycle Specification](strategy-membership-cycle/membership-cycle-specification.md)
- [Membership Cycle Implementation Plan](strategy-membership-cycle/membership-cycle-implementation-plan.md)
- [Membership Cycle Config Schema](strategy-membership-cycle/membership-cycle-config-schema.md)
