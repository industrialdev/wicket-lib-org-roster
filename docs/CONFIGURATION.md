# Configuration Reference

The library's behavior is primarily controlled by a central configuration array, which can be modified using the `wicket/acc/orgman/config` filter.

## Core Sections

### `roster`
- `strategy`: (string) The active roster management logic. Options: `direct`, `cascade`, `groups`.

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

### `additional_seats`
- `enabled`: (bool) Toggle for the seat purchase feature.
- `sku`: The WooCommerce product SKU used for seat purchases.
- `form_id` / `form_slug`: Mapping for the Gravity Form used in the purchase flow.

### `ui`
- `member_list.use_unified`: (bool) Enables the modern, search-centric list view.
- `member_view.use_unified`: (bool) Enables the modern reactive member cards.
- `member_card_fields`: Configures which fields (Name, Job Title, Email, etc.) are visible and editable on the member cards.

## Example Filter Usage

```php
add_filter('wicket/acc/orgman/config', function($config) {
    // Force Groups strategy
    $config['roster']['strategy'] = 'groups';
    
    // Add a custom role to managers
    $config['permissions']['manage_members'][] = 'senior_coordinator';
    
    return $config;
});
```
