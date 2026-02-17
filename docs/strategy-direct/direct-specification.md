# Direct Strategy: Roster Specification

## 1) Scope and Intent
- Define roster behavior when `roster.strategy = direct`.
- Keep `cascade`, `groups`, and `membership_cycle` behavior unchanged.
- Capture current implementation contracts for add/remove, membership resolution, and role assignment.
- Strategy key constant for this spec: `direct`.

## 2) Source Documents Analyzed
- `docs/SPECS.md`
- `docs/STRATEGIES.md`
- `docs/CONFIGURATION.md`
- `src/Services/Strategies/DirectAssignmentStrategy.php`
- `templates-partials/process/add-member.php`
- `templates-partials/process/remove-member.php`

## 3) Requirement Consolidation

### 3.1 Core Model
- Management scope is organization-first.
- Membership seat assignment occurs in the add flow.
- Default membership target is resolved from organization unless explicit membership UUID is provided in context.

### 3.2 Membership Resolution Rules
- Resolution order in strategy:
  1. Use `context['membership_uuid']` or `context['membership_id']` when provided.
  2. Validate explicit membership belongs to target organization.
  3. Otherwise resolve by organization via membership service.
- If no valid membership is resolvable, add fails.

### 3.3 Add Member Behavior
- Add flow performs:
  - person create/update,
  - person-to-organization connection creation when missing,
  - membership seat assignment,
  - base role assignment,
  - configured auto-role assignment,
  - additional form role assignment,
  - touchpoint logging,
  - assignment email dispatch.
- Input roles are sanitized and normalized.
- `membership_owner` assignment is filtered when `permissions.prevent_owner_assignment = true`.

### 3.4 Remove Member Behavior
- Strategy remove requires `person_membership_id` in context.
- Direct strategy remove behavior intentionally:
  - removes org-scoped roles,
  - does not end membership in strategy method,
  - does not end organization relationship in strategy method.
- Owner removal block is config-driven by `permissions.prevent_owner_removal`.

### 3.5 Duplicates and Guards
- Add-member process handler includes duplicate check when a membership UUID is present.
- Duplicate check is email + membership scoped against active records.

### 3.6 Display and UX
- Direct strategy uses organization management screens with heading `Manage Organizations`.
- Unified search/pagination/list behavior applies through shared templates and endpoints.

## 4) Backward Compatibility Contract (Non-Negotiable)
- `direct` remains default strategy.
- Existing config keys remain valid and are not renamed.
- Existing process endpoints and payload structure remain stable.
- No behavior drift is introduced into other strategies.

## 5) Security and Validation
- Nonce and permission checks run in process handlers before strategy calls.
- Request fields are sanitized before use.
- Explicit membership overrides are org-scope validated.
- Mutations fail closed on missing required context.

## 6) Test Strategy (Pest)
- Coverage should include:
  - context membership UUID resolution,
  - mismatch rejection for cross-org membership UUID,
  - fallback resolution when explicit UUID is absent,
  - regression safety across non-direct strategies.

## 7) Acceptance Criteria
- Add assigns person to intended org membership and roles.
- Remove strips org roles and respects owner-protection config.
- Explicit membership UUID path validates org scope.
- Direct mode remains stable as default strategy.

## 8) Config Schema Reference
- Direct-relevant config contract is documented in `docs/strategy-direct/direct-config-schema.md`.
