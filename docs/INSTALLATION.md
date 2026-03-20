# Installation

## Requirements

- PHP 8.2+
- WordPress
- Composer autoload available before `OrgMan.php` runs

Optional integrations:

- WooCommerce for additional seats
- Gravity Forms for the additional seats purchase form
- Wicket/MDP helper functions supplied by the host application

## Repository Configuration

Add this repository entry to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:industrialdev/wicket-lib-org-roster.git"
    }
  ]
}
```

## Composer Sync Scripts

Templates and assets must be publicly accessible under the content path. Add these scripts to your `composer.json` to automatically sync the library after install/update:

```json
{
  "scripts": {
    "orgman:sync-lib": [
      "@php vendor/industrialdev/wicket-lib-org-roster/.ci/sync-orgman-lib.php"
    ],
    "post-install-cmd": [
      "@orgman:sync-lib"
    ],
    "post-update-cmd": [
      "@orgman:sync-lib"
    ]
  }
}
```

This syncs the package to:
- `web/app/libs/wicket-lib-org-roster` (Bedrock-style)
- `wp-content/libs/wicket-lib-org-roster` (standard WordPress)

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

The sync script (see "Composer Sync Scripts" above) is required because templates and assets must be publicly accessible under the content path (`web/app/libs/` or `wp-content/libs/`).
