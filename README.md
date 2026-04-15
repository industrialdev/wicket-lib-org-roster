# Org Roster Library

Composer library for Wicket organization and roster management in WordPress.

## Entrypoint

- `OrgManagement\OrgMan`

## What Ships Today

- page injection for organization roster account pages
- four roster strategies:
  - `direct`
  - `cascade`
  - `groups`
  - `membership_cycle`
- Datastar-oriented template partials and process handlers
- WooCommerce and Gravity Forms additional-seats flow
- CSV bulk upload flow gated by config

## Quick Start

```bash
composer require industrialdev/wicket-lib-org-roster
```

```php
use OrgManagement\OrgMan;

add_action('after_setup_theme', static function (): void {
    add_filter('wicket/acc/orgman/config', static function (array $config): array {
        return $config;
    });

    if (class_exists(OrgMan::class)) {
        OrgMan::getInstance();
    }
}, 20);
```

## Runtime Notes

- default strategy is `direct`
- unknown strategy keys fall back to `cascade`
- bulk upload is disabled by default
- the library resolves `base_path` and `base_url` automatically, with filters available for overrides

## Documentation

For complete documentation, see **[docs/index.md](docs/index.md)**.
