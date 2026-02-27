# Changelog

All notable changes to this project are documented in this file.

## [0.4.2] - 2026-02-27

### Fixed
- Hardened Bedrock sync script (`.ci/sync-orgman-lib.php`) to avoid permission failures on shared hosts when replacing existing `web/app/libs/wicket-lib-org-roster` contents.
- Sync now stages a full copy and performs an atomic directory swap instead of unlinking files in place.

### Changed
- Bumped package version in `composer.json` to `0.4.2`.

## [0.4.1] - 2026-02-27

### Added
- New Bedrock sync script for public library deployment:
  - `.ci/sync-orgman-lib.php`
- New UI config keys for customizable non-groups management heading:
  - `ui.organization_list.use_custom_title`
  - `ui.organization_list.custom_title`

### Changed
- Updated WordPress bootstrap guidance and runtime loading flow:
  - `wicket_orgman_*` naming in site bootstrap examples
  - `wicket_orgman_load_autoloader()` execution inside `after_setup_theme`
- Updated Bedrock installation guidance to run sync via:
  - `@php vendor/industrialdev/wicket-lib-org-roster/.ci/sync-orgman-lib.php`
- Organization management heading templates now read configurable title values in non-groups mode.
- Bumped package version in `composer.json` to `0.4.1`.

### Documentation
- Updated `docs/INSTALLATION.md`, `docs/CONFIGURATION.md`, `docs/SPECS.md`, `docs/ARCHITECTURE.md`, and `docs/TESTING.md` for Bedrock + standard WordPress loading parity and current bootstrap naming.

## [0.4.0] - 2026-02-27

### Added
- New canonical configuration defaults class: `src/Config/OrgManConfig.php`.
- New case-collision CI guard script: `.ci/check-case-collisions.php`.
- New Composer script: `check:case-collisions` (also included in `production` workflow).
- New unit coverage in `OrgManAssetBaseUrlTest` for base URL resolution when installed under root `vendor/...`.

### Changed
- Switched `OrgMan` runtime bootstrapping to Composer PSR-4 autoloading and removed manual case-insensitive dependency loading.
- `OrgMan` now fails fast with a clear runtime error when Composer autoload is missing.
- Base URL resolution now supports both `WP_CONTENT_DIR` and `ABSPATH` rooted installations, including root `vendor/...` package paths.
- Updated dependency constraint to `starfederation/datastar-php:^1@dev` (currently resolving to `1.0.0-RC.5`).
- Bumped package version in `composer.json` to `0.4.0`.

### Documentation
- Updated installation/configuration/spec docs to reference `src/Config/OrgManConfig.php` and root Composer install behavior.

## [0.3.7] - 2026-02-26

### Added
- New bulk-upload duplicate-hash error path for previously processed files:
  - error code: `bulk_duplicate_finished_job`
  - message now indicates the matching CSV hash was already processed and advises uploading a different CSV payload.
- New theme override starter stylesheet:
  - `public/css/org-roster-theme-overrides.demo.css`
- New unit coverage in `BulkMemberUploadServiceTest` for rejecting duplicate uploads when a matching file hash already exists on a completed job.

### Changed
- Bulk upload duplicate-hash lookup now checks all known jobs (`find_job_by_hash`) and branches behavior by job status:
  - active jobs (`queued`/`processing`) keep the in-progress rejection path
  - non-active matches now return the new finished-job rejection path.
- Refined bulk-upload UI markup in `templates-partials/members-bulk-upload.php`:
  - introduced scoped classes (`orgman-bulk-upload`, `orgman-bulk-upload__template-link`, `orgman-bulk-upload__file-input`)
  - adjusted supporting helper-copy typography and submit button alignment.
- Standardized modal close-button markup across member/group templates with `orgman-modal__close` for consistent styling hooks.
- Expanded `public/css/modern-orgman-static.css` with new bulk-upload control styles and reusable modal close-button styles/focus states.

### Documentation
- Updated `docs/INSTALLATION.md` with a recommended theme-override workflow:
  - enqueue a child-theme stylesheet that depends on `orgman-modern`
  - copy-and-customize guidance for the demo override file
  - example `wp_enqueue_scripts` implementation and revised section numbering.

## [0.3.6] - 2026-02-26

### Added
- New optional permission config for role-only organization management access:
  - `permissions.role_only_management_access.enabled` (default `false`)
  - `permissions.role_only_management_access.allowed_roles` (default `['membership_owner']`)

### Changed
- Organization list resolution can now include org-scoped role-derived organizations when role-only access is enabled.
- Permission checks can now bypass active-membership requirement for configured role intersections when role-only access is enabled, and role-only allowlisted users can access organization-management visibility surfaces (including organization profile and bulk-upload entry points when enabled).
- Role parsing for org-scoped permissions now tolerates API relationship shape variants (`resource`/`organization`, `organization`/`organizations`) and ignores global roles.
- Updated documentation references and MSA baseline config example to include site-specific role-only access override guidance.

## [0.3.5] - 2026-02-25

### Fixed
- Bulk member upload no longer creates duplicate members in groups strategy when a person is already actively assigned:
  - Added pre-add duplicate checks for active group membership by email/person within group and organization scope.
  - Rows that map to an already-assigned group member now count as `skipped` instead of creating duplicates/failing.
  - Added unit coverage for group-scope duplicate detection in `BulkMemberUploadServiceTest`.

## [0.3.3] - 2026-02-25

### Added
- Asynchronous bulk member upload processing with WP-Cron job batches:
  - New service: `OrgManagement\Services\BulkMemberUploadService`.
  - New cron hook: `wicket_orgman_process_bulk_upload_job`.
  - Per-job option storage with isolated records for parallel execution:
    - job index key: `wicket_orgman_bulk_upload_job_ids`
    - job record key prefix: `wicket_orgman_bulk_upload_job_{job_id}`
- Hash-based duplicate upload protection for active jobs:
  - SHA-256 file fingerprinting on enqueue.
  - Rejects enqueue if the same file is already `queued` or `processing`.
- Bulk upload configuration expansion:
  - New config key: `bulk_upload.batch_size` (default `25`, bounded in service).
- New tests for async bulk pipeline:
  - `tests/Unit/Services/BulkMemberUploadServiceTest.php`.

### Changed
- Refactored `templates-partials/process/bulk-upload-members.php` into a thin request handler:
  - keeps nonce/permission/context checks and SSE response rendering.
  - delegates CSV processing/enqueueing to `BulkMemberUploadService`.
- Added background processing wiring in `OrgManagement\OrgMan`:
  - service dependency loading/initialization.
  - cron action registration and scheduled batch dispatch.
- Updated bulk processor CSV parsing calls to explicit `fgetcsv(..., escape)` arguments for forward compatibility.

### Fixed
- Preserved groups and non-groups bulk-upload behavior via updated regression assertions after architecture split.
- Prevented cross-job interference by moving from monolithic option payloads to per-job option records.

### Logging
- Added always-on bulk upload lifecycle logging (including production):
  - enqueue accepted/rejected
  - batch start/end
  - row-level skip/fail/add outcomes
  - schedule failures
  - completion summaries

## [0.3.1] - 2026-02-19

### Added
- Config-gated CSV member bulk upload flow in roster member views:
  - New config key: `ui.member_list.show_bulk_upload` (default `false`).
  - New process handler: `templates-partials/process/bulk-upload-members.php`.
  - New reusable UI partial: `templates-partials/members-bulk-upload.php`.
- Strategy-aware CSV processing via `MemberService->add_member(...)` per row, with additive-only behavior and duplicate-skip handling.

### Changed
- Positioned bulk upload control in the member-management CTA region to match legacy flow intent.
- Aligned `Bulk Upload Members` CTA width/position with `Add Member` by rendering it in a dedicated stacked container.
- Added backend feature-gate enforcement so the process endpoint is disabled when `ui.member_list.show_bulk_upload` is `false`.
- Updated standalone bulk upload page flow:
  - if a user can bulk-manage exactly one organization, the page now redirects to that org via `?org_uuid=...`
  - if a user can bulk-manage multiple organizations, the page now shows a simplified organization selector first
- Added groups-strategy standalone bulk-upload flow:
  - standalone page now lists manageable groups directly in groups mode (with one-group auto-redirect)
  - bulk processor now accepts/validates `group_uuid` and routes row context to groups strategy (`group_uuid`, per-row role fallback)

### Fixed
- Added case-insensitive dependency loading in `OrgMan` to prevent Linux-only fatals when deployed directory casing differs (e.g., `helpers/` vs `Helpers/`).

### Documentation
- Updated all docs under `docs/` to include:
  - new bulk upload feature behavior
  - `ui.member_list.show_bulk_upload` configuration reference
  - strategy-specific bulk upload status and expectations
  - architecture/frontend/testing notes for the new endpoint flow

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
