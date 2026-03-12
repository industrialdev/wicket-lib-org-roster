# Org Roster Library (Composer)

Composer library for Wicket organization/group roster management.

- Entrypoint: `OrgManagement\OrgMan`
- Autoload: `OrgManagement\` => `src/`
- Frontend: template-driven UI + Datastar interactions

## Quick Start

Install in your theme/app:

```bash
composer require industrialdev/wicket-lib-org-roster
```

Load Composer autoload, then initialize OrgMan on `after_setup_theme` (recommended) after registering your config filter:

```php
use OrgManagement\OrgMan;

add_action('after_setup_theme', static function (): void {
    add_filter('wicket/acc/orgman/config', static function (array $config): array {
        $config['ui']['member_list']['show_bulk_upload'] = true; // default false
        return $config;
    });

    if (class_exists(OrgMan::class)) {
        OrgMan::getInstance();
    }
}, 20);
```

See full install guidance in `docs/INSTALLATION.md`.

Deployment note:
- Prefer syncing the package from root `vendor/...` into a public `libs/` runtime path after install/update.
- Standard WordPress target: `wp-content/libs/wicket-lib-org-roster`
- Bedrock target: `web/app/libs/wicket-lib-org-roster`

## Strategies

Supported strategy keys:
- `direct`
- `cascade`
- `groups`
- `membership_cycle`

Strategy resolution is runtime-configured via `ConfigService::getRosterMode()`.

## Coding Conventions

- PHP code follows PSR-12.
- Class names use PascalCase.
- Method and property names use camelCase across services, strategies, and helpers.
- Internal snake_case compatibility wrappers are not maintained, except `OrgMan::get_instance()` for theme compatibility.
- External WordPress/WooCommerce/Wicket API names remain unchanged when upstream uses underscores.

## Debug Markup Notes

- Injected OrgMan content includes debug comments inside the `ORGMAN:BEGIN/END` block:
  - library path
  - library version from `Helper::getLibraryVersion()`

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Design](docs/DESIGN.md)
- [Specifications](docs/SPECS.md)
- [Strategies](docs/STRATEGIES.md)
- [Configuration](docs/CONFIGURATION.md)
- [Frontend](docs/FRONTEND.md)
- [Testing](docs/TESTING.md)
- [Changelog](CHANGELOG.md)

## Assets

Primary stylesheet:
- `public/css/modern-orgman-static.css`

If your library install path differs, override:
- `wicket/acc/orgman/base_path`
- `wicket/acc/orgman/base_url`
