# Cascade Strategy: Roles and Permissions

## Permission Gates
- Add/remove permissions are checked in process handlers through shared permission helper gates.
- Global permission config controls role access:
  - `permissions.add_members`
  - `permissions.remove_members`
  - `permissions.manage_members`

## Role Assignment on Add
- Base role from `member_addition.base_member_role` is assigned.
- Auto roles from `member_addition.auto_assign_roles` are assigned.
- Optional additional roles can be merged with relationship-mapped roles.
- `membership_owner` can be filtered by `permissions.prevent_owner_assignment`.

## Owner Removal Protection
- Cascade strategy remove blocks organization owner removal.

## Success Criteria
- Cascade role assignment follows configured rules and relationship mappings.
- Unauthorized or forbidden removals are blocked.
