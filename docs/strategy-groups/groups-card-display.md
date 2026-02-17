# Groups Strategy: Card Display

## Group List Screen
- Lists only manageable groups (role + tag + organization attached).
- Group cards are organized under organizations the user can manage.
- In groups strategy, page heading is `Manage Groups`.

## Group Members Screen
- Displays group members scoped to the manager's org association within that group.
- Supports search by member identity and pagination.
- Uses unified member list/view by default:
  - `groups.ui.use_unified_member_list = true`
  - `groups.ui.use_unified_member_view = true`

## Member Roles in UI
- Supports configured roster roles (default: `member`, `observer`).
- `show_edit_permissions` is disabled by default for groups mode.
