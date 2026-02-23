# Groups Strategy: Card Display

## Organization List Screen
- Renders a group list in groups mode (not organization cards).
- In groups strategy, page heading is `Manage Groups`.
- Shows `Groups Found: N`.
- Group list rows are built from active group memberships that match the configured roster tag.
- Row metadata includes:
  - Group name.
  - Organization name label (resolved name first; identifier fallback if needed).
  - Current user role label (`My Role`).
- Row actions:
  - If role is manageable: show `Group Profile` and `Manage Members`.
  - If role is not manageable: show no management links.
- Redirect behavior:
  - Exactly one row: redirect directly to `organization-members` for that group.
  - More than one row: render list and no redirect.

## Group Members Screen
- Displays group members scoped to the manager's org association within that group.
- Supports search by member identity and pagination.
- Uses unified member list/view by default:
  - `groups.ui.use_unified_member_list = true`
  - `groups.ui.use_unified_member_view = true`

## Member Roles in UI
- Supports configured roster roles (default: `member`, `observer`).
- `show_edit_permissions` is disabled by default for groups mode.
