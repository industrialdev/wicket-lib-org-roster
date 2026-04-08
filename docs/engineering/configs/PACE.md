# PACE Configuration

Source of truth: `../pace-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

This document mirrors the current site override. If it drifts, update the site config first, then update this file.

## Active Strategy

- `membership.strategy = cascade`

## Notes

PACE's MDP membership has "Cascading Membership Settings" enabled with cascade type "Cascade to organization's relationships only" and allowed resource "Employee". This means MDP will only auto-grant a membership to a person when they are connected to the org with relationship type `employee`. The library default is `Position`, so the relationship type must be overridden here or the MDP cascade will never fire.

## Current Override Paths

### `membership`

- `membership.strategy = cascade`

### `relationships`

- `relationships.defaults.type = employee`

## Current Config Function

```php
function wicket_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'cascade';

    // MDP cascade membership is configured to trigger on 'employee' relationship type only.
    // The library default is 'Position' — override it here so the cascade fires correctly.
    $config['relationships']['defaults']['type'] = 'employee';

    return $config;
}
```
