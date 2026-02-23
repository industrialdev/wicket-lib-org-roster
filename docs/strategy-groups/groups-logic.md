# Groups Strategy Logic

## Intent
Implement roster management where access and membership operations are scoped by group role and group tag.

## Eligibility Pipeline
1. Load active group memberships for current person.
2. Resolve group metadata.
3. Keep groups that match `groups.tag_name` (with configured case sensitivity).
4. Build groups landing list from active, tagged memberships (includes non-manage roles).
5. Mark each row with `can_manage` based on role membership in `groups.manage_roles`.
6. Deduplicate by `group_uuid` and sort by group name.
7. If result count is 1, redirect to `organization-members` for that group; otherwise render list.

## Organization Association Model
- Group membership records carry org association in `custom_data_field`.
- `groups.additional_info` defines:
  - `key` (default: `association`)
  - `value_field` (default: `name`)
  - `fallback_to_org_uuid` (default: `true`)
- Member list visibility is constrained by normalized org-scope matching (association value and/or org UUID).

## Add Member Flow
1. Validate `group_uuid` and role.
2. Validate actor can manage the target group.
3. Resolve person by name/email.
4. Ensure person-org relationship exists.
5. Apply seat limit check when role is seat-limited.
6. Create group member with aligned custom-data org association.
7. Send assignment notification.

## Remove Member Flow
1. Validate `group_uuid`.
2. Validate actor can manage the target group.
3. Find matching group member id by person + role + org association.
4. Remove membership by configured mode:
  - end-date, or
  - hard delete.

## Display Logic
- Strategy route `organization-management` renders group rows (not organization cards).
- Groups landing shows `Groups Found: N` and one row per active tagged group membership.
- Group row action links (`Group Profile`, `Manage Members`) are rendered only when `can_manage = true`.
- In groups mode, primary heading is `Manage Groups`.
- Group detail pages reuse shared templates with groups context.
- Group rows resolve organization label from included org data, then `wicket_get_organization`, then org identifier fallback.

## Operational Notes
- Group-tag availability can vary by endpoint payload.
- Service now fetches `/groups/{group_uuid}` when included tags are missing.
- Seat-limit check for seat-limited roles currently inspects up to the fetched member page (50 in add flow context).
