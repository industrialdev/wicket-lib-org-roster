# CCHL Example Overrides

This is a site-level example that only uses keys supported by the current library.

## Typical Overrides

- add CCHL-specific auto roles on member addition
- normalize CCHL role aliases
- hide community roles from role pickers

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['member_addition']['auto_assign_roles'] = [
        'supplemental_member',
        'CCHL Member Community',
    ];

    $config['roles']['aliases'] = [
        'cchl_member_community' => 'cchlmembercommunity',
    ];

    $config['member_addition_form']['fields']['permissions']['excluded_roles'] = [
        'Cchlmembercommunity',
        'cchlmembercommunity',
    ];
    $config['edit_permissions_modal']['excluded_roles'] = [
        'Cchlmembercommunity',
        'cchlmembercommunity',
    ];

    return $config;
});
```
