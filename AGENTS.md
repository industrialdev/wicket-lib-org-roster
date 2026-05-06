# Project Overview
`wicket-lib-org-roster` is a PHP library for managing organization rosters in Wicket WordPress environments. It supports multiple roster management strategies, integrates with WooCommerce for seat purchases, and uses Datastar for a reactive frontend experience.

## Project Structure & Module Organization
- `src/`: Core PHP library code (Namespace: `WicketORM\`).
    - `OrgMan.php`: Singleton orchestrator; handles initialization, hooks, and asset loading.
    - `Controllers/`: REST API and config controllers (e.g., `ApiController`, `ConfigurationController`).
    - `Services/`: Business logic services (e.g., `OrganizationService`, `MemberService`, `AdditionalSeatsService`).
    - `Services/Strategies/`: Implementations of `RosterManagementStrategy` (`DirectAssignmentStrategy`, `CascadeStrategy`, `GroupsStrategy`, `MembershipCycleStrategy`).
    - `Helpers/`: Utility classes (e.g., `DatastarSSE`, `TemplateHelper`, `ConfigHelper`, `PermissionHelper`).
    - `Config/`: Configuration definitions (`OrgManConfig`).
- `templates/`: Main page templates injected into WordPress content.
- `templates-partials/`: Reusable template parts and process handlers (e.g., `process/add-member.php`).
- `public/`: Frontend assets.
    - `css/`: Static vanilla CSS assets (`modern-orgman-static.css` is the runtime stylesheet).
    - `js/`: Datastar error handlers and shared scripts.
- `docs/`: Product, engineering, and end-user docs (`docs/product/`, `docs/engineering/`, `docs/guides/`, plus strategy notes).

## Key Architectural Patterns
- **Orchestrator Pattern**: `OrgMan` is the central entry point.
- **Service Layer**: Business logic is encapsulated in services initialized by `OrgMan`.
- **Strategy Pattern**: `RosterManagementStrategy` supports `direct`, `cascade`, `groups`, and `membership_cycle` modes.
- **Reactive UI**: Uses [Datastar](https://data-star.dev/) for real-time DOM updates via Server-Sent Events (SSE) and signals, reducing full-page reloads.
- **Caching**: Extensive use of WordPress transients for API response caching, managed via `ConfigHelper`.

## Build, Test, and Development Commands
- `composer install`: Installs PHP dependencies and sets up autoloading.
- `composer check`: Runs configured static/style checks (`cs:lint`).
- `composer cs:lint`: PHP CS Fixer dry run.
- `composer cs:format`: Applies PHP CS Fixer formatting.
- `composer check:case-collisions`: Validates case-sensitive path collisions under `src/`.
- `composer setup-hooks`: Installs local git pre-push hook.
- No npm build pipeline is required for frontend CSS in this library.

## Coding Style & Naming Conventions
- PHP 8.2+ required; follow PSR-12.
- Use early returns and keep methods focused.
- **Naming**: Classes in `src/` use PascalCase; class methods/properties use camelCase (PSR-12 aligned). Variables may use snake_case in WordPress-oriented template/process files where that pattern is already established. *Strictly follow existing patterns in the file you are editing.*
- **Compatibility policy**: Do not add broad snake_case compatibility wrappers for internal library methods. Keep only the `OrgMan::get_instance()` theme bridge alias.
- **External API exceptions**: Keep upstream WordPress/WooCommerce/Wicket API function and method names exactly as provided, even when they use underscores.
- **Datastar Signals**: Use descriptive signal names in templates (e.g., `signals.show_modal`).
- **Datastar interactivity**: Prefer Datastar state to swap content or drive visibility. Do not rely on boolean attribute toggling for core interactivity such as `disabled` on primary search, pagination, or modal controls.
- **Utility classes (`wt_` prefixed)**: Do not assume Tailwind-like utilities exist. Any new `wt_` utility class added in templates must be declared in `public/css/modern-orgman-static.css` in the utility section (or reuse an existing declared utility).

## Testing Guidelines
- This package currently does **not** ship a `tests/` directory or a `composer test` script.
- Validate touched files with syntax/style checks (`composer check`, `php -l <file>` as needed).
- Stack-level automated tests for this library live in `qa/` (for example, `composer test:unit:org-roster` from `qa/`).
- Do not claim Pest/Brain Monkey coverage for this package unless tests are added to this repository.

## Integrations
- **Wicket API**: Primarily accessed via `wicket_api_client()` and related helper functions.
- **WooCommerce + Gravity Forms**: Additional-seats flow is implemented via `AdditionalSeatsService`, `GravityFormsHelper`, and order-processing hooks in `OrgMan`.
- **Datastar**: Frontend reactivity; uses `DatastarSSE` helper for standard success/error responses.

## Security & Configuration
- Use `PermissionService` for all capability and role checks.
- Sensitive data should not be logged; follow existing logging patterns and keep PII out of logs.
- Configuration is filterable via `wicket/org-roster/config`.
- Base paths/URLs are filterable via `wicket/org-roster/base_path` and `wicket/org-roster/base_url`.

## Member Card Display — All Templates

Any change to member card field visibility (name, email, job title, description, roles, relationship type) must be applied consistently across **all** templates that render per-member card output. These templates all call `Helper::should_show_*` / `Helper::should_hide_*` methods to gate each field:

| Template | Context | Helper namespace used |
|---|---|---|
| `templates-partials/members-list.php` | Direct-assignment / default member list | `OrgHelpers\Helper::` (aliased) |
| `templates-partials/members-list-unified.php` | Unified member list (org context) | `OrgHelpers\Helper::` (aliased) |
| `templates-partials/member-details.php` | SSE lazy-load fragment (details block only; no name/header) | `\WicketORM\Helpers\Helper::` |
| `templates-partials/group-members-list.php` | Groups strategy member list | `WicketORM\Helpers\Helper::` |
| `templates-partials/members-view-unified.php` | Unified member view / read-only card display | `OrgHelpers\Helper::` (aliased) |

**Rule:** when adding or modifying a `should_show_*` helper in `src/Helpers/Helper.php`, grep all five templates above and apply the guard everywhere the corresponding field is rendered. Skipping any one of them creates a config-inconsistency where the field appears in some views but not others.
