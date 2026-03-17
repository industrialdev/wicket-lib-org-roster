# ESCRS Configuration

Source of truth: `../escrs-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

## Active Strategy

- `membership.strategy = membership_cycle`

## Canonical Overrides

### `membership`

- `membership.strategy = membership_cycle`
- `membership.cycle.permissions.add_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.remove_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.purchase_seat_roles = ['membership_owner']`
- `membership.cycle.prevent_owner_removal = true`
- `membership.cycle.require_explicit_membership_uuid = true`

### `presentation`

- `presentation.member_view.search_clear_requires_submit = true`

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `membership_cycle.permissions.add_roles -> membership.cycle.permissions.add_member_roles`
- `membership_cycle.permissions.remove_roles -> membership.cycle.permissions.remove_member_roles`
- `membership_cycle.permissions.purchase_seats_roles -> membership.cycle.permissions.purchase_seat_roles`
- `membership_cycle.permissions.prevent_owner_removal -> membership.cycle.prevent_owner_removal`
- `membership_cycle.member_management.require_explicit_membership_uuid -> membership.cycle.require_explicit_membership_uuid`
- `membership_cycle.ui.search_clear_requires_submit -> presentation.member_view.search_clear_requires_submit`

## Copy/Paste Config Function

```php
function wicket_child_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'membership_cycle';
    $config['membership']['cycle']['permissions']['add_member_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['permissions']['remove_member_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['permissions']['purchase_seat_roles'] = [
        'membership_owner',
    ];
    $config['membership']['cycle']['prevent_owner_removal'] = true;
    $config['membership']['cycle']['require_explicit_membership_uuid'] = true;

    $config['presentation']['member_view']['search_clear_requires_submit'] = true;

    return $config;
}
```
