# Configuration Reference

The library's behavior is primarily controlled by a central configuration array, which can be modified using the `wicket/acc/orgman/config` filter.

## Core Sections

### `roster`
- `strategy`: (string) The active roster management logic. Options: `direct`, `cascade`, `groups`, `membership_cycle`.

### `roles`
Defines the internal slugs for key organizational roles:
- `owner`: Default `membership_owner`.
- `manager`: Default `membership_manager`.
- `editor`: Default `org_editor`.

### `permissions`
Maps roles to specific capabilities. Each key (e.g., `manage_members`) takes an array of roles that are granted that permission.
- `relationship_based_permissions`: (bool) If true, roles are automatically assigned/removed based on the person-to-organization relationship type.
- `relationship_roles_map`: Defines which roles map to which relationship types (e.g., `ceo` => `['org_editor', 'membership_manager']`).

### `member_addition`
- `auto_assign_roles`: Roles given to every new member added via the roster.
- `base_member_role`: The default role for new entries.
- `auto_opt_in_communications`: Configuration for automatic email sublist subscriptions during member addition.

### `groups` (Groups Strategy Only)
- `tag_name`: The MDP tag used to identify groups eligible for roster management.
- `manage_roles`: Group roles allowed to manage the roster.
- `roster_roles`: Roles available to be assigned to roster members (e.g., `member`, `observer`).
- `seat_limited_roles`: Roles that are capped (typically one per organization per group).
- `removal.mode`: `end_date` (soft delete) or `delete` (hard delete).

### `membership_cycle` (Membership Cycle Strategy Only)
- `strategy_key`: Strategy identifier (`membership_cycle`).
- `permissions`:
  - `add_roles`: Roles allowed to add members in cycle-scoped mode.
  - `remove_roles`: Roles allowed to remove members in cycle-scoped mode.
  - `purchase_seats_roles`: Roles allowed to purchase additional seats.
  - `prevent_owner_removal`: Prevents organization owner removal in cycle-scoped remove flow.
- `member_management`:
  - `require_explicit_membership_uuid`: Requires explicit `membership_uuid` for cycle-scoped mutations.

### `additional_seats`
- `enabled`: (bool) Toggle for the seat purchase feature.
- `sku`: The WooCommerce product SKU used for seat purchases.
- `form_id` / `form_slug`: Mapping for the Gravity Form used in the purchase flow.

### `ui`
- `member_list.use_unified`: (bool) Enables the modern, search-centric list view.
- `member_view.use_unified`: (bool) Enables the modern reactive member cards.
- `member_card_fields`: Configures shared member-card field visibility across strategies.
  - `job_title.enabled`: used.
  - `description.enabled`: used.
  - `roles.enabled`: used (controls role display in member cards/lists).

## Strategy Examples

### Example: `direct` Strategy

```php
add_filter('wicket/acc/orgman/config', function ($config) {
    $config['roster']['strategy'] = 'direct';

    // Keep direct mode strict by allowing only manager/owner to mutate roster.
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = ['membership_manager', 'membership_owner'];

    return $config;
});
```

### Example: `cascade` Strategy

```php
add_filter('wicket/acc/orgman/config', function ($config) {
    $config['roster']['strategy'] = 'cascade';

    // Enable relationship-based role assignment used by cascade-style flows.
    $config['permissions']['relationship_based_permissions'] = true;
    $config['permissions']['relationship_roles_map']['ceo'] = ['org_editor', 'membership_manager'];

    return $config;
});
```

### Example: `groups` Strategy

```php
add_filter('wicket/acc/orgman/config', function ($config) {
    $config['roster']['strategy'] = 'groups';

    // Configure group-managed roster behavior.
    $config['groups']['tag_name'] = 'Roster Management';
    $config['groups']['roster_roles'] = ['member', 'observer'];
    $config['groups']['seat_limited_roles'] = ['member'];
    $config['groups']['removal']['mode'] = 'end_date';

    return $config;
});
```

### Example: `membership_cycle` Strategy

```php
add_filter('wicket/acc/orgman/config', function ($config) {
    $config['roster']['strategy'] = 'membership_cycle';

    // Strategy-local permission overrides (does not change global defaults).
    $config['membership_cycle']['permissions']['add_roles'] = ['membership_manager'];
    $config['membership_cycle']['permissions']['remove_roles'] = ['membership_manager'];
    $config['membership_cycle']['permissions']['purchase_seats_roles'] = ['membership_owner', 'membership_manager'];

    return $config;
});
```
