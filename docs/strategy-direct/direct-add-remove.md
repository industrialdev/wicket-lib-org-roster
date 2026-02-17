# Direct Strategy: Add / Remove

## Add a Member
- Required:
  - Organization UUID
  - First name
  - Last name
  - Email
- Optional context:
  - `membership_uuid` / `membership_id`
  - `relationship_type`
  - `relationship_description`
  - additional role list
- Add behavior:
  - Create/update person.
  - Ensure org relationship exists.
  - Assign membership seat.
  - Assign base, auto, and submitted roles.

## Remove a Member
- Required for strategy remove:
  - `org_id`
  - `person_uuid`
  - `person_membership_id` in context
- Remove behavior:
  - Enforce owner-protection when configured.
  - Remove org-scoped roles.
  - Keep membership/relationship active at strategy layer.

## Restrictions
- Membership override is rejected when it does not belong to the organization.
- Owner removal may be blocked by config.
- Missing `person_membership_id` fails remove.

## Success Criteria
- Add and remove complete with deterministic direct-strategy side-effects.
- Explicit membership targeting behaves safely and predictably.
