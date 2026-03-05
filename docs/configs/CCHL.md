# CCHL Roster Configuration Baseline

Date: 2026-03-03

This document captures CCHL-specific roster overrides that must remain site-level and must not be moved into shared library defaults.

## Why This Exists

- Shared library defaults are strategy-safe and cross-site neutral.
- CCHL community roles are site-specific and must only be enabled in CCHL site config.

## Required CCHL Site Overrides

Apply these keys in the CCHL site theme/plugin config filter (`wicket/acc/orgman/config`):

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    // CCHL-only auto-roles on member addition.
    $config['member_addition']['auto_assignRoles'] = [
        'supplemental_member',
        'CCHL Member Community',
    ];

    // Normalize CCHL community role variants to a canonical slug.
    $config['roles']['aliases'] = [
        'cchl_member_community' => 'cchlmembercommunity',
    ];

    // Keep CCHL community roles out of assignable role controls.
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

## Verification Checklist

- [x] `member_addition.auto_assignRoles` is explicitly set in CCHL site config.
- [x] Shared library `member_addition.auto_assignRoles` default remains `[]`.
- [x] CCHL community roles are excluded from generic permissions selectors.
- [x] Non-CCHL sites (for example MSA) do not auto-assign CCHL community roles.
