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

## 3) Theme Styling Overrides (Recommended)

OrgMan ships its own base stylesheet (`orgman-modern`).  
To customize the UI safely from your theme, enqueue a second stylesheet that depends on `orgman-modern`.

Recommended child-theme path (WordPress convention):
- `assets/css/org-roster.css`

If your theme already uses a different convention (for example `public/css/wicket-orgman.css`), use that path instead.

Starter demo file (inside this library):
- `public/css/org-roster-theme-overrides.demo.css`
- Copy it into your theme and rename to `assets/css/org-roster.css` (or your preferred theme path).

Example copy command (run from your child theme root):

```bash
cp vendor/industrialdev/wicket-lib-org-roster/public/css/org-roster-theme-overrides.demo.css assets/css/org-roster.css
```

Add this to `custom/org-roster.php`:

```php
<?php

add_action('wp_enqueue_scripts', static function (): void {
    // Only load overrides when OrgMan styles are present.
    if (!wp_style_is('orgman-modern', 'enqueued') && !wp_style_is('orgman-modern', 'registered')) {
        return;
    }

    $relative_path = 'assets/css/org-roster.css';
    $file_path = trailingslashit(get_stylesheet_directory()) . $relative_path;
    if (!file_exists($file_path)) {
        return;
    }

    wp_enqueue_style(
        'wicket-child-org-roster',
        trailingslashit(get_stylesheet_directory_uri()) . $relative_path,
        ['orgman-modern'],
        (string) filemtime($file_path)
    );
}, 30);
```

The demo file uses native modern CSS nesting/cascade and is scoped to `.wicket-orgman`.

## 4) Include Bootstrap File From Theme `functions.php`

Example include list:

```php
$wicket_child_includes = [
    'config-child.php',
    'acf.php',
    'org-roster.php',
];
```

## 5) Strategy/Behavior Configuration (Optional)

Inside the bootstrap filter, set the strategy and client-specific config:

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['roster']['strategy'] = 'membership_cycle';
    $config['ui']['member_list']['show_bulk_upload'] = true; // default is false
    return $config;
});
```

For complete membership-cycle options, see:
- `docs/CONFIGURATION.md`
- `docs/STRATEGIES.md`
- `docs/strategy-membership-cycle/membership-cycle-specification.md`

## 6) Verification Checklist

1. Child theme has `vendor/autoload.php`.
2. `OrgMan::get_instance()` is called on `after_setup_theme`.
3. My Account CPT page slug exists: `organization-management`.
4. User has relevant memberships/roles in Wicket.
5. No fatal errors in PHP/WP logs.

## Common Failure Mode

If the page renders but org list is empty with no OrgMan execution evidence, verify the bootstrap hook first. Using `plugins_loaded` can prevent expected initialization timing in some theme setups.
