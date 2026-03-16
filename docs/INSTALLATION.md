# Installation

## Requirements

- PHP 8.2+
- WordPress
- Composer autoload available before `OrgMan.php` runs

Optional integrations:

- WooCommerce for additional seats
- Gravity Forms for the additional seats purchase form
- Wicket/MDP helper functions supplied by the host application

## Basic Setup

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

## Required WordPress Pages

The library expects account-page slugs matching:

- `organization-management`
- `organization-profile`
- `organization-members`
- `organization-members-bulk`
- `supplemental-members`

## Path and URL Resolution

The library auto-resolves asset paths for common layouts, including:

- `wp-content/libs/...`
- `web/app/libs/...`
- root `vendor/...`

You can override this with:

- `wicket/acc/orgman/base_path`
- `wicket/acc/orgman/base_url`

## Deployment Note

Many installations sync the Composer package into a public `libs/` directory after install or update so templates and assets are available under the content path.
