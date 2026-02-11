# Org Roster Library (Composer)

This directory is a Composer-installed library for Wicket organization roster management. It exposes the `OrgManagement\OrgMan` entrypoint and ships templates, assets, and service classes.

## Usage

- Install via Composer path/VCS repository into `web/app/libs/wicket-lib-org-roster`
- Autoload classes with Composer (PSR-4: `OrgManagement\` => `src/`)
- Initialize via `\OrgManagement\OrgMan::get_instance()`

### Composer Install

```bash
composer require industrialdev/wicket-lib-org-roster
```

Ensure your application loads Composer's autoloader before using OrgMan or templates.

### Datastar SDK

The Datastar PHP SDK is a Composer dependency (`starfederation/datastar-php`). Do not vendor the SDK in this library.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Strategies](docs/STRATEGIES.md)
- [Configuration](docs/CONFIGURATION.md)
- [Design](docs/DESIGN.md)
- [Frontend](docs/FRONTEND.md)
- [Testing](docs/TESTING.md)
- [Unified Specifications](docs/SPECS.md)

## Assets

Assets are served from the library directory. If your install path differs, override:
- `wicket/acc/orgman/base_path`
- `wicket/acc/orgman/base_url`

# Styling

The org-management frontend uses static vanilla CSS utilities and BEM-style component classes.

## CSS Assets

- `public/css/modern-orgman-static.css` - Primary stylesheet enqueued by `OrgMan`

## Conventions

- Utility-style classes use the `wt_` prefix (scoped namespace)
- Component classes follow BEM naming where possible
- Theme values should come from CSS variables (no hardcoded utility colors)
