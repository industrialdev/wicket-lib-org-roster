# Groups Strategy Logic

## Intent
Implement roster management where access and membership operations are scoped by group role and group tag.

## Eligibility Pipeline
1. Load active group memberships for current person.
2. Keep records whose membership role is in `groups.manage_roles`.
3. Resolve group metadata.
4. Keep groups that match `groups.tag_name` (with configured case sensitivity).
5. Keep groups attached to an organization.
6. Build organization cards keyed by organization UUID.
7. Synthesize missing organizations from manageable groups when base organization list is incomplete.

## Organization Association Model
- Group membership records carry org association in `custom_data_field`.
- `groups.additional_info` defines:
  - `key` (default: `association`)
  - `value_field` (default: `name`)
  - `fallback_to_org_uuid` (default: `true`)
- Member list visibility is constrained by matching org association value.

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
- Strategy route `organization-management` renders manageable organizations derived from group access.
- Route paginates organization cards using `ui.organization_list.page_size` and `org_page`.
- In groups mode, primary heading is `Manage Groups`.
- Group detail pages reuse shared templates with groups context.

## Operational Notes
- Group-tag availability can vary by endpoint payload.
- Service now fetches `/groups/{group_uuid}` when included tags are missing.
- Seat-limit check for seat-limited roles currently inspects up to the fetched member page (50 in add flow context).
