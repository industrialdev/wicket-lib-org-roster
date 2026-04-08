# Membership Cycle Strategy: Roles And Permissions

Membership-cycle mode has strategy-local permission keys.

## Relevant Keys

- `membership_cycle.permissions.add_roles`
- `membership_cycle.permissions.remove_roles`
- `membership_cycle.permissions.purchase_seats_roles`
- `membership_cycle.permissions.prevent_owner_removal`

## Defaults

- add: `membership_manager`
- remove: `membership_manager`
- purchase seats: `membership_owner`, `membership_manager`, `org_editor`
- owner removal blocked: `true`
