# Installation

This guide shows the supported way to load `industrialdev/wicket-lib-org-roster` from a WordPress child theme.

## Why `after_setup_theme`

Initialize OrgMan on `after_setup_theme` (priority `20`), not `plugins_loaded`.

`after_setup_theme` is the reliable point where theme code, Composer autoloading, and my-account template behavior are consistently available for this library.

## 1) Install With Composer (inside child theme)

From your child theme root (example: `web/app/themes/wicket-child`):

```bash
composer init -n \
  && composer config repositories.wicket-lib-org-roster vcs https://github.com/industrialdev/wicket-lib-org-roster.git \
  && composer config minimum-stability RC \
  && composer config prefer-stable true \
  && composer require industrialdev/wicket-lib-org-roster:^0
```

Minimal `composer.json` example:

```json
{
  "minimum-stability": "RC",
  "prefer-stable": true,
  "repositories": {
    "wicket-lib-org-roster": {
      "type": "vcs",
      "url": "https://github.com/industrialdev/wicket-lib-org-roster.git"
    }
  },
  "require": {
    "industrialdev/wicket-lib-org-roster": "^0.2"
  }
}
```

Why RC stability is required:
- `industrialdev/wicket-lib-org-roster` currently depends on `starfederation/datastar-php:^1.0.0-RC.5`.
- Without `minimum-stability` set to `RC` (or lower), Composer will reject that transitive dependency.

If `composer.json` already exists, run only:

```bash
composer config repositories.wicket-lib-org-roster vcs https://github.com/industrialdev/wicket-lib-org-roster.git \
  && composer config minimum-stability RC \
  && composer config prefer-stable true \
  && composer require industrialdev/wicket-lib-org-roster:^0
```

## 2) Bootstrap File (`custom/org-roster.php`)

Use this pattern:

```php
<?php

use OrgManagement\OrgMan;

defined('ABSPATH') || exit;

if (file_exists(get_stylesheet_directory() . '/vendor/autoload.php')) {
    require_once get_stylesheet_directory() . '/vendor/autoload.php';
}

add_action('after_setup_theme', static function (): void {
    static $booted = false;
    if ($booted) {
        return;
    }
    $booted = true;

    add_filter('wicket/acc/orgman/config', static function (array $config): array {
        // mutate config...
        return $config;
    });

    if (class_exists(OrgMan::class)) {
        OrgMan::get_instance();
    }
}, 20);
```

Important: register `wicket/acc/orgman/config` before `OrgMan::get_instance()` so initial service/config boot uses your overrides.

## 2.1) Hardening (Recommended)

Use a named callback and register the filter only once:

```php
<?php

use OrgManagement\OrgMan;

defined('ABSPATH') || exit;

if (file_exists(get_stylesheet_directory() . '/vendor/autoload.php')) {
    require_once get_stylesheet_directory() . '/vendor/autoload.php';
}

function wicket_child_orgman_config(array $config): array
{
    // mutate config...
    return $config;
}

add_action('after_setup_theme', static function (): void {
    static $booted = false;
    if ($booted) {
        return;
    }
    $booted = true;

    if (!has_filter('wicket/acc/orgman/config', 'wicket_child_orgman_config')) {
        add_filter('wicket/acc/orgman/config', 'wicket_child_orgman_config');
    }

    if (class_exists(OrgMan::class)) {
        OrgMan::get_instance();
    }
}, 20);
```

## 3) Include Bootstrap File From Theme `functions.php`

Example include list:

```php
$wicket_child_includes = [
    'config-child.php',
    'acf.php',
    'org-roster.php',
];
```

## 4) Strategy/Behavior Configuration (Optional)

Inside the bootstrap filter, set the strategy and client-specific config:

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['roster']['strategy'] = 'membership_cycle';
    return $config;
});
```

For complete membership-cycle options, see:
- `docs/CONFIGURATION.md`
- `docs/STRATEGIES.md`
- `docs/strategy-membership-cycle/membership-cycle-specification.md`

## 5) Verification Checklist

1. Child theme has `vendor/autoload.php`.
2. `OrgMan::get_instance()` is called on `after_setup_theme`.
3. My Account CPT page slug exists: `organization-management`.
4. User has relevant memberships/roles in Wicket.
5. No fatal errors in PHP/WP logs.

## Common Failure Mode

If the page renders but org list is empty with no OrgMan execution evidence, verify the bootstrap hook first. Using `plugins_loaded` can prevent expected initialization timing in some theme setups.
