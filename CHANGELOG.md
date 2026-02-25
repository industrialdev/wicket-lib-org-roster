# Changelog

All notable changes to this project are documented in this file.

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
