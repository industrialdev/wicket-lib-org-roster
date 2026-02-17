# Cascade Strategy: Roster Specification

## 1) Scope and Intent
- Define roster behavior when `roster.strategy = cascade`.
- Preserve behavior of `direct`, `groups`, and `membership_cycle` strategies.
- Document current cascade contracts around add/remove side-effects and permission assumptions.
- Strategy key constant for this spec: `cascade`.

## 2) Source Documents Analyzed
- `docs/SPECS.md`
- `docs/STRATEGIES.md`
- `docs/CONFIGURATION.md`
- `src/Services/Strategies/CascadeStrategy.php`
- `templates-partials/process/add-member.php`
- `templates-partials/process/remove-member.php`

## 3) Requirement Consolidation

### 3.1 Core Model
- Management scope is organization-first with cascade-oriented side-effects.
- Membership target is resolved from current person memberships by organization.
- Add flow connects person, assigns membership seat, then applies role assignment.

### 3.2 Add Member Behavior
- Add flow performs:
  - person resolve/create,
  - membership resolution for target organization,
  - person-to-organization connection creation when needed,
  - membership seat assignment,
  - base role assignment,
  - auto-role assignment,
  - relationship-mapped role assignment (when enabled),
  - notification email dispatch.
- Relationship mapping behavior is controlled by:
  - `permissions.relationship_based_permissions`
  - `permissions.relationship_roles_map`

### 3.3 Remove Member Behavior
- Remove requires `person_membership_id` in context.
- Strategy remove behavior:
  - blocks organization owner removal,
  - end-dates person membership,
  - removes org-scoped roles.
- Owner-protection is always enforced in cascade strategy implementation.

### 3.4 Roles and Assignment Rules
- Base role: `member_addition.base_member_role`.
- Auto roles: `member_addition.auto_assign_roles`.
- Additional submitted roles are merged with relationship-mapped roles (if enabled).
- `membership_owner` assignment can be filtered when `permissions.prevent_owner_assignment = true`.

### 3.5 Display and UX
- Cascade strategy uses organization management screens with heading `Manage Organizations`.
- Shared unified list/search/pagination templates are used.
- Seat callouts and additional seats purchase UX are reused from shared services.

## 4) Backward Compatibility Contract (Non-Negotiable)
- `cascade` remains a supported legacy-compatible strategy.
- Unknown strategy keys currently fall back to `cascade` in strategy registry behavior.
- Existing endpoint payloads and shared templates remain stable.
- No behavior drift is introduced for other strategies.

## 5) Security and Validation
- Process handlers enforce nonce and permission checks before strategy execution.
- Input fields are sanitized prior to mutation calls.
- Remove fails closed when `person_membership_id` is missing.
- Membership seat assignment fails closed on helper/API failure.

## 6) Test Strategy (Pest)
- Coverage should include:
  - strategy wiring/fallback safety,
  - cascade add/remove regression behavior,
  - owner-removal protection,
  - role assignment gates and relationship-based role mapping behavior.

## 7) Acceptance Criteria
- Cascade add creates/links person, assigns membership seat, and applies configured roles.
- Cascade remove end-dates target person membership and removes org roles.
- Owner cannot be removed through cascade strategy.
- Existing non-cascade strategy behavior remains unchanged.

## 8) Config Schema Reference
- Cascade-relevant config contract is documented in `docs/strategy-cascade/cascade-config-schema.md`.
