# Groups Strategy: Add / Remove

## Add a Member
- Allowed only when actor has a managing role for the target group and org association.
- Required input:
  - First name
  - Last name
  - Email
  - `group_uuid`
  - Role in `groups.roster_roles`
- Add behavior:
  - Create or resolve person.
  - Ensure organization relationship exists.
  - Attach org association in group-membership `custom_data_field`.
  - Enforce seat-limited-role constraint (`groups.seat_limited_roles`).

## Remove a Member
- Allowed only when actor has managing role for target group and org association.
- Required input:
  - `group_uuid`
  - person UUID
  - role (when role-scoped remove is required)
- Remove behavior:
  - Resolve matching group member record.
  - Apply configured removal mode:
    - `end_date` (default)
    - `delete`

## Restrictions
- Users cannot mutate groups they do not manage.
- Users cannot manage roster entries associated with other organizations inside the same group.
- Org-scope matching supports normalized UUID/name/identifier comparison for robust cross-endpoint data variance.
- Invalid or missing `group_uuid` fails with explicit error.

## Success Criteria
- Eligible managers can add/remove members in their managed groups only.
- Org association isolation is preserved within shared groups.
- Seat-limited roles cannot exceed configured per-group/per-org cap.
