# ESCRS Configuration

Source of truth: `../escrs-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

This document mirrors the current site override. If it drifts, update the site config first, then update this file.

## Active Strategy

- `membership.strategy = membership_cycle`

## Current Override Paths

### `membership`

- `membership.strategy = membership_cycle`
- `membership.cycle.permissions.add_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.remove_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.purchase_seat_roles = ['membership_owner']`
- `membership.cycle.prevent_owner_removal = true`
- `membership.cycle.require_explicit_membership_uuid = true`

### `presentation`

- `presentation.member_view.use_unified = true`
- `presentation.member_view.search_clear_requires_submit = true`
- `presentation.member_list.use_unified = true`

## Current Config Function

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
    $config['presentation']['member_view']['use_unified'] = true;
    $config['presentation']['member_list']['use_unified'] = true;
    $config['presentation']['member_view']['search_clear_requires_submit'] = true;

    return $config;
}
```
