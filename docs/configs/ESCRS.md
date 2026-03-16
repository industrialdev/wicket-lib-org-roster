# ESCRS Example Overrides

`membership_cycle` exists in the current library, but only a small additive config surface is implemented.

## Supported ESCRS-Style Overrides Today

- `roster.strategy = membership_cycle`
- cycle-specific add/remove/purchase role overrides
- explicit membership UUID requirement
- shared bulk-upload toggle through `ui.member_list.show_bulk_upload`

## Current Example

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['roster']['strategy'] = 'membership_cycle';

    $config['membership_cycle']['permissions']['add_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['remove_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['purchase_seats_roles'] = [
        'membership_owner',
    ];
    $config['membership_cycle']['permissions']['prevent_owner_removal'] = true;
    $config['membership_cycle']['member_management']['require_explicit_membership_uuid'] = true;

    $config['ui']['member_list']['show_bulk_upload'] = true;
    $config['ui']['member_list']['use_unified'] = true;
    $config['ui']['member_view']['use_unified'] = true;

    return $config;
});
```

## Not In The Library Config Today

Do not document or depend on these as built-in keys:

- cycle-tab UI config
- membership-label whitelist config under `membership_cycle.bulk_upload`
- custom membership status mapping config
- cycle-specific seats UI namespaces

If ESCRS needs them, they are follow-up implementation work, not current library behavior.
