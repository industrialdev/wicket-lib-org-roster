# Direct Strategy: Roles and Permissions

## Permission Gates
- Add/remove permissions use shared permission helper checks at process-handler level.
- Default role gates come from global `permissions` config keys:
  - `permissions.add_members`
  - `permissions.remove_members`
  - `permissions.manage_members`

## Role Assignment Rules on Add
- Base role from `member_addition.base_member_role` is always assigned.
- Auto roles from `member_addition.auto_assign_roles` are assigned.
- Submitted roles are filtered by member-addition form allow/exclude config.
- `membership_owner` assignment can be blocked by `permissions.prevent_owner_assignment`.

## Owner Removal Protection
- Remove path can block owner removal when `permissions.prevent_owner_removal = true`.

## Success Criteria
- Direct-mode mutations honor global permission config.
- Role assignment/removal remains organization-scoped.
