# Direct Strategy Logic

## Intent
Provide explicit, deterministic roster mutations in organization scope without cascade side-effects.

## Add Flow
1. Validate/sanitize input (handler layer).
2. Resolve person via create-or-update path.
3. Resolve membership UUID (explicit context override or org resolver fallback).
4. Ensure person-to-organization connection exists.
5. Assign person to organization membership seat.
6. Assign base member role.
7. Assign auto-configured roles.
8. Assign additional submitted roles (filtered by config allow/exclude lists).
9. Log touchpoint and attempt assignment email.

## Membership Targeting
- Preferred: explicit membership UUID in context.
- Fallback: organization membership resolver.
- Explicit membership UUID must belong to selected organization.

## Remove Flow
1. Validate/sanitize input (handler layer).
2. Validate owner-protection guard when enabled.
3. Require `person_membership_id` in strategy remove context.
4. Remove all org-scoped roles from target person.
5. Log removal touchpoint.

## Relationship and Membership Notes
- Strategy remove does not end membership or relationship directly.
- Process-level removal path can end person memberships and relationships depending on active strategy branch.
- For direct mode, relationship ending path is skipped in current process handler logic.

## Operational Notes
- Assignment email failures are logged but do not block add success.
- Seat assignment helper failures return explicit errors.
- Role assignment uses existing Wicket helper functions and fails fast on hard errors.
