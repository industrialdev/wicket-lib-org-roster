# Groups Strategy: Roster Specification

## 1) Scope and Intent
- Define the behavior of roster management when `roster.strategy = groups`.
- Keep `direct`, `cascade`, and `membership_cycle` behavior unchanged.
- Document current implementation contracts and known constraints.
- Strategy key constant for this spec: `groups`.

## 2) Source Documents Analyzed
- `docs/SPECS.md`
- `docs/STRATEGIES.md`
- `docs/CONFIGURATION.md`
- `src/Services/GroupService.php`
- `src/Services/Strategies/GroupsStrategy.php`
- `templates-partials/organization-list.php`

## 3) Requirement Consolidation

### 3.1 Core Model
- Roster ownership is group-centric.
- Access is based on active `group_members` records for the current user.
- Group roster visibility is restricted to memberships that match:
  - allowed managing role, and
  - roster-management group tag, and
  - organization association.

### 3.2 Group Eligibility Rules
- A group is shown only when all criteria are true:
  - User has an active group role in `groups.manage_roles`.
  - Group carries `groups.tag_name` (default: `Roster Management`, case-insensitive by default).
  - Group is attached to an organization.
- Additional tag fallback is supported:
  - If included group payload omits `tags`, the service fetches `/groups/{group_uuid}` and re-evaluates tag eligibility.

### 3.3 Managing Roles
- Default managing role slugs:
  - `president`
  - `delegate`
  - `alternate_delegate`
  - `council_delegate`
  - `council_alternate_delegate`
  - `correspondent`
- Final role policy is config-driven by `groups.manage_roles`.

### 3.4 Roster Roles and Seat Rules
- Default roster roles:
  - `member`
  - `observer`
- Seat-limited role defaults:
  - `member` only (`groups.seat_limited_roles`).
- Seat limitation contract:
  - One seat-limited role per org per group.
  - Non-seat-limited roles (for example `observer`) are not capped by this rule.

### 3.5 Add / Remove
- Add requires:
  - `group_uuid`
  - roster role in `groups.roster_roles`
  - actor can manage the target group.
- Add behavior:
  - Resolve/create person.
  - Ensure person has org relationship.
  - Create `group_members` record with org association in `custom_data_field`.
- Remove behavior:
  - Resolve group member by person + role + org association.
  - Removal mode is configurable:
    - `end_date` (default)
    - `delete`

### 3.6 List/Search/Pagination
- Manageable group list and group member list are paginated.
- Search is supported:
  - manageable groups by group name.
  - members by group search endpoint (or standard list fallback).
- Unified member list/view templates are default for groups mode.

### 3.7 UI Labels
- In groups mode, the top-level management heading must be `Manage Groups`.
- In non-groups modes, heading remains `Manage Organizations`.

## 4) Backward Compatibility Contract (Non-Negotiable)
- `groups` remains opt-in via strategy switch.
- Existing strategy classes and behavior outside `groups` remain unchanged.
- Existing config keys remain valid; overrides are additive.
- Existing endpoints and template routing remain stable.

## 5) Security and Validation
- Group mutations require group-level access validation (`can_manage_group`).
- Role slugs are sanitized and validated against config.
- Request fields are sanitized before use.
- Mutations fail closed when required context is missing or invalid.

## 6) Test Strategy (Pest)
- Strategy-level tests should cover:
  - role gating by `groups.manage_roles`
  - tag filtering behavior (including `/groups/{id}` fallback)
  - seat-limited-role guard
  - add/remove access checks
- Regression tests should prove no behavior drift in `direct`, `cascade`, `membership_cycle`.

## 7) Acceptance Criteria
- Eligible managers can see roster-management groups on `organization-management`.
- Non-eligible roles/groups are excluded.
- `Manage Groups` heading appears when strategy is `groups`.
- Add/remove only works for groups and org association the actor manages.
- Tests cover role/tag eligibility and keep cross-strategy safety.

## 8) Config Schema Reference
- Current config contract is documented in `docs/strategy-groups/groups-config-schema.md`.
