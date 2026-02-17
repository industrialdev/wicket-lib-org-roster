# Groups Strategy: Card Display

## Organization List Screen
- Lists manageable organization cards derived from group access (role + tag + organization attached).
- If base organization data is missing, cards are synthesized from manageable groups so managers still see eligible orgs.
- In groups strategy, page heading is `Manage Groups`.
- Organization cards are paginated (page size from `ui.organization_list.page_size`, query arg `org_page`).
- Group-focused card metadata includes:
  - Membership tier status.
  - Current role labels.
  - Group `Type` and `Tag(s)` aggregates.

## Group Members Screen
- Displays group members scoped to the manager's org association within that group.
- Supports search by member identity and pagination.
- Uses unified member list/view by default:
  - `groups.ui.use_unified_member_list = true`
  - `groups.ui.use_unified_member_view = true`

## Member Roles in UI
- Supports configured roster roles (default: `member`, `observer`).
- `show_edit_permissions` is disabled by default for groups mode.
