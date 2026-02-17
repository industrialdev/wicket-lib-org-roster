# Groups Strategy: Roles and Permissions

## Managing Roles
A user can manage a group roster only if their active group-membership role is in `groups.manage_roles`.

Default role slugs:
- `president`
- `delegate`
- `alternate_delegate`
- `council_delegate`
- `council_alternate_delegate`
- `correspondent`

## Roster Roles
Roles assignable within group roster entries are controlled by `groups.roster_roles`.

Default roster role slugs:
- `member`
- `observer`

## Access Rules
- Group management is denied without a valid active managing role.
- Access is scoped by organization association metadata.
- Role and tag checks are config-driven and validated server-side.

## Success Criteria
- Eligible managers can view and mutate only their allowed group roster scope.
- Non-managers cannot add/remove group roster entries.
