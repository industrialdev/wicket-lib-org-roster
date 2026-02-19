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
        OrgMan::get_instance();
    }
}, 20);
```

See full install guidance in `docs/INSTALLATION.md`.

## Bulk Upload (CSV)

Feature flag:
- `ui.member_list.show_bulk_upload` (default `false`)

UI behavior:
- Member list pages show a `Bulk Upload Members` CTA (same style as `Add Member`) that opens a modal.
- CSV template file is served from:
  - `public/templates/roster_template.csv`

Standalone page:
- Slug: `organization-members-bulk`
- Supports strategy-aware selection before upload form:
  - `direct` / `cascade` / `membership_cycle`: org selection (auto-redirect when exactly one manageable org).
  - `groups`: manageable group selection (auto-redirect when exactly one manageable group).

Processor behavior:
- Endpoint: `templates-partials/process/bulk-upload-members.php`
- Routes row creation through `MemberService->add_member(...)`, so active strategy is respected.
- In groups mode, processor validates `group_uuid` access and passes `group_uuid` + role context to strategy.

## Strategies

Supported strategy keys:
- `direct`
- `cascade`
- `groups`
- `membership_cycle`

Strategy resolution is runtime-configured via `ConfigService::get_roster_mode()`.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Strategies](docs/STRATEGIES.md)
- [Configuration](docs/CONFIGURATION.md)
- [Frontend](docs/FRONTEND.md)
- [Testing](docs/TESTING.md)
- [Specifications](docs/SPECS.md)
- [Changelog](CHANGELOG.md)

## Assets

Primary stylesheet:
- `public/css/modern-orgman-static.css`

If your library install path differs, override:
- `wicket/acc/orgman/base_path`
- `wicket/acc/orgman/base_url`
