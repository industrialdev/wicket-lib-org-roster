# Repository Guidelines

## Project Overview
`wicket-lib-org-roster` is a PHP library for managing organization rosters in Wicket WordPress environments. It supports multiple roster management strategies, integrates with WooCommerce for seat purchases, and uses Datastar for a reactive frontend experience.

## Project Structure & Module Organization
- `src/`: Core PHP library code (Namespace: `OrgManagement\`).
    - `OrgMan.php`: Singleton orchestrator; handles initialization, hooks, and asset loading.
    - `controllers/`: REST API controllers (e.g., `ApiController`, `BusinessInfoController`).
    - `services/`: Business logic services (e.g., `OrganizationService`, `MemberService`).
    - `services/strategies/`: Implementations of `RosterManagementStrategy` (Cascade, Direct, Groups).
    - `helpers/`: Utility classes (e.g., `DatastarSSE`, `TemplateHelper`, `ConfigHelper`).
    - `config/`: Configuration definitions.
- `templates/`: Main page templates injected into WordPress content.
- `templates-partials/`: Reusable template parts and process handlers (e.g., `process/add-member.php`).
- `public/`: Frontend assets.
    - `css/`: Static vanilla CSS assets (`modern-orgman-static.css` is the runtime stylesheet).
    - `js/`: Datastar error handlers and shared scripts.
- `tests/`: Pest-based test suite.
    - `Unit/`: Service and helper unit tests.
    - `helpers/`: WordPress shims for testing environment.
- `docs/`: Specification and reference documentation.

## Key Architectural Patterns
- **Orchestrator Pattern**: `OrgMan` is the central entry point.
- **Service Layer**: Business logic is encapsulated in services, which are lazily instantiated when possible.
- **Strategy Pattern**: `RosterManagementStrategy` allows for different roster behaviors (e.g., membership-based vs. group-based management).
- **Reactive UI**: Uses [Datastar](https://data-star.dev/) for real-time DOM updates via Server-Sent Events (SSE) and signals, reducing full-page reloads.
- **Caching**: Extensive use of WordPress transients for API response caching, managed via `ConfigHelper`.

## Build, Test, and Development Commands
- `composer install`: Installs PHP dependencies and sets up autoloading.
- `composer test`: Runs the Pest test suite.
- No npm build pipeline is required for frontend CSS in this library.

## Coding Style & Naming Conventions
- PHP 8.2+ required; follow PSR-12.
- Use early returns and keep methods focused.
- **Naming**: Classes in `src/` use PascalCase; methods and variables use snake_case (standard WP style) or camelCase depending on the context (Services often use camelCase for methods, while helpers use snake_case). *Strictly follow existing patterns in the file you are editing.*
- **Datastar Signals**: Use descriptive signal names in templates (e.g., `signals.show_modal`).

## Testing Guidelines
- Framework: **Pest** with **Brain Monkey** and **Mockery**.
- Mocking: Use anonymous class stubs or closure binding for testing classes with private/protected members.
- Location: `tests/Unit/` with `*Test.php` naming.
- Ensure `composer test` passes before any submission.

## Integrations
- **Wicket API**: Primarily accessed via `wicket_api_client()` and related helper functions.
- **WooCommerce**: Handles "Additional Seats" purchases; integrated via `AdditionalSeatsService` and order processing hooks in `OrgMan`.
- **Datastar**: Frontend reactivity; uses `DatastarSSE` helper for standard success/error responses.

## Security & Configuration
- Use `PermissionService` for all capability and role checks.
- Sensitive data should not be logged; use `wc_get_logger` for standard logging.
- Configuration is filterable via `wicket/acc/orgman/config`.
- Base paths/URLs are filterable via `wicket/acc/orgman/base_path` and `wicket/acc/orgman/base_url`.
