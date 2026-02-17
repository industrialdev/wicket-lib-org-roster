# Cascade Strategy Logic

## Intent
Support roster management in environments that rely on cascade-oriented side-effects and legacy helper integrations.

## Add Flow
1. Validate/sanitize request (handler layer).
2. Resolve/create person from input profile.
3. Resolve organization membership UUID for target org.
4. Ensure person-to-organization connection exists.
5. Assign person to resolved membership seat.
6. Assign base member role.
7. Assign configured auto roles.
8. Optionally merge relationship-mapped roles and assign additional roles.
9. Send assignment notification email.

## Membership Resolution
- Cascade strategy resolves membership with organization-scoped lookup.
- If no corporate membership is found, add fails.

## Remove Flow
1. Validate/sanitize request (handler layer).
2. Block organization owner removal.
3. Require `person_membership_id` context.
4. End-date target person membership.
5. Remove all org-scoped roles for the target person.

## Relationship Mapping
- When enabled, relationship type can add mapped roles automatically.
- Mappings are configured in `permissions.relationship_roles_map`.

## Operational Notes
- Strategy depends on legacy helper availability (for role and membership assignment).
- Notifications are logged; failures are observable through logger output.
- Cascade semantics differ from direct remove semantics (cascade remove ends membership).
