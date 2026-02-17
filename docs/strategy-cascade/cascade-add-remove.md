# Cascade Strategy: Add / Remove

## Add a Member
- Required:
  - Organization UUID
  - First name
  - Last name
  - Email
- Optional context:
  - `relationship_type`
  - `relationship_description`
  - additional `roles`
- Add behavior:
  - Resolve/create person.
  - Resolve org membership UUID.
  - Create org relationship when missing.
  - Assign membership seat.
  - Assign base, auto, and optional relationship-driven roles.

## Remove a Member
- Required:
  - `org_id`
  - `person_uuid`
  - `person_membership_id` in context
- Remove behavior:
  - Reject owner removal.
  - End-date person membership.
  - Remove org-scoped roles.

## Restrictions
- Missing `person_membership_id` fails remove.
- Missing valid membership for organization fails add.
- Owner removal is blocked.

## Success Criteria
- Cascade add/remove side-effects match legacy expectations.
- Membership and roles stay organization-scoped.
