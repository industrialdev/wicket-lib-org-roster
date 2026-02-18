# Changelog

All notable changes to this project are documented in this file.

## [0.2.8] - 2026-02-18

### Fixed
- Added `component-button` to all `<button>` elements using the `button` class.
- Prevented unwanted `<br>`/`<p>` artifacts in Org Roster injected markup by scoping post-processing to OrgMan-injected content segments.
- Normalized button and inline span markup in roster templates to reduce auto-formatting artifacts in rendered HTML.

## [0.2.0] - 2026-02-11

### Added
- Membership-cycle roster strategy implementation (`MembershipCycleStrategy`) with strategy wiring coverage tests.
- Expanded test suite for strategy behavior and config contracts (`DirectAssignmentStrategyTest`, `MembershipCycleStrategyTest`, `StrategiesWiringTest`).
- Strategy-specific documentation set under `docs/strategy-membership-cycle/` and consolidated strategy index docs.
- Release guardrails with `.ci/pre-push` hook and hardened Composer script workflow.

### Changed
- Refactored source layout to PSR-4-compatible casing (`src/Controllers`, `src/Services`, `src/Helpers`, `src/Services/Strategies`).
- Updated member and organization templates for unified list/view behavior aligned with membership-cycle strategy rules.
- Synced architecture/configuration/frontend/testing docs with current strategy model and runtime behavior.

### Build
- Updated Composer dependencies and lockfile for the expanded test/tooling baseline used at `0.2.0`.

## [0.1.0] - 2026-02-04

### Added
- Initial Composer library packaging for `industrialdev/wicket-lib-org-roster` with `OrgManagement\OrgMan` singleton entrypoint.
- Core WordPress integration hooks for:
  - page-content injection on Org Roster my-account screens
  - REST route registration
  - asset enqueueing
  - hypermedia template routing
- Service layer foundation:
  - `OrganizationService`
  - `MemberService`
  - `MembershipService`
  - `PermissionService`
  - `GroupService`
  - `ConnectionService`
  - `BusinessInfoService`
  - `DocumentService`
  - `SubsidiaryService`
  - `NotificationService`
  - `AdditionalSeatsService`
  - supporting config/batch/person services
- Strategy architecture for roster behavior with initial strategy implementations:
  - cascade
  - direct assignment
  - groups
- Template system and partials for organization roster flows:
  - organization listing/profile/members screens
  - member listing/search/pagination/modals
  - group member management views
  - business info and subsidiary management views
  - document management views
- Datastar-driven reactive UX patterns for search, partial updates, and modal/process responses.
- Hypermedia endpoint handling (`action=hypermedia&template=...`) for template-only HTML responses.
- WooCommerce additional-seats integration:
  - order processing hooks
  - return URL handling
  - line-item metadata transfer for org/membership context
- Config and helper foundation:
  - filterable config contract
  - template/config/permission helpers
  - relationship and Datastar SSE helpers
  - base path/base URL filter support for asset resolution
- Static frontend asset bundle under `public/css` and `public/js` with utility-scoped styling conventions.
- Test harness baseline (Pest, Brain Monkey, Mockery) and Composer scripts for test/lint/format workflows.
- Security baseline for WordPress context:
  - capability-aware service checks
  - nonce usage in form/process flows
  - template path sanitization and traversal guards
