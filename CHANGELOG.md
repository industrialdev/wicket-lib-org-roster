# Changelog

All notable changes to this project are documented in this file.

## [0.4.10] - 2026-03-09

### Fixed
- Fixed cascade add-member recovery for pre-existing broken person-to-organization relationships:
  - when strategy is `cascade` and the target person does not already hold the organization membership seat, the add flow now detects active `person_to_organization` relationships for the same org, end-dates all active matches, and creates a fresh relationship so downstream/system-managed cascade automation can assign the membership seat.
  - added detailed wc log events for stale-relationship detection, end-dating, and recreation during cascade add.

### Added
- New cascade repair config default:
  - `member_addition.repair_stale_relationship_without_membership = true`
- New `ConnectionService` helpers to support stale-relationship repair:
  - enumerate active `person_to_organization` relationships for a person/org pair
  - end-date all active relationships for that person/org pair

### Security
- Reduced HTML debug comment path disclosure in injected OrgMan content:
  - `Wicket Roster Library Path` now renders an `ABSPATH`-relative `./...` path when possible, with safe public-path fallbacks for Bedrock and standard WordPress layouts, instead of exposing the full server filesystem path.

## [0.4.7] - 2026-03-05

### Fixed
- Completed PSR-12 naming migration across internal class APIs and updated in-repo callsites to camelCase.
- Updated cascade strategy add flow to relationship-only mutation:
  - added pre-add seat-capacity guard (`active_assignments_count` vs effective max assignments).
  - preserved downstream/system-managed membership assignment behavior for cascade mode.

### Added
- New regression coverage for cascade contract enforcement:
  - `tests/Unit/Helpers/CascadeStrategyRegressionTest.php`

### Documentation
- Updated cascade strategy docs to match implementation:
  - relationship-only add flow in cascade mode
  - seat-capacity validation before relationship creation
  - downstream/system responsibility for membership assignment
- Updated naming policy docs to reflect PSR-12 camelCase convention and the single compatibility alias (`OrgMan::get_instance()`).

## [0.4.6] - 2026-03-04

### Fixed
- Prevented duplicate cascade seat/member assignment behavior:
  - Cascade add-member now aborts on membership lookup errors instead of treating errors as "not found".
  - Membership-seat assignment now re-checks existing membership assignment and short-circuits when already assigned.
  - Membership lookup now paginates through person-membership rows.
- Improved role-update behavior when duplicate person-membership rows exist:
  - `MemberService::update_member_roles()` now prefers active/in-grace rows and falls back deterministically.
  - Added regression test coverage in `tests/Unit/Services/MemberServiceTest.php`.
- Fixed active-membership guard in role updates to treat `in_grace` memberships as eligible when `member_edit.require_active_membership_for_role_updates` is enabled.
- Fixed edit-permissions role update requests to resolve and use the current organization membership UUID server-side (prevents stale posted membership UUIDs from causing false inactive-member errors).
- Strengthened role-update active-membership validation with an `active_at=now` API fallback when listed person-membership attributes appear inactive, and added detailed role-update debug logging in wc logs.
- Fixed organization-management card status to reflect organization membership status (not only current user active-membership gate), and removed inactive suffix from tier labels.
- Fixed organization title click behavior: titles are only links when user can edit org or manage members.
- Fixed Edit Permissions modal title duplication by using one computed title string.
- Fixed org card organization-name fallback by broadening attribute name resolution and organization detail fallback.
- Fixed OrgMan notification script rendering on account pages by moving inline notification JS to an enqueued asset (prevents `wpautop` from injecting `<p>` tags into script blocks).
- Fixed Add Member modal stale state across opens by resetting form values/messages/submitting/success state on open, close, and successful submit in unified and legacy member flows.
- Hardened OrgMan injected-content cleanup against `wpautop` corruption by stripping paragraph/line-break artifacts inside and around `<script>/<style>` blocks.
- Fixed modal/process success responses to avoid automatic full-page reloads by default (`DatastarSSE::renderSuccess` now reloads only when explicitly requested).
- Improved Add Member modal success UX using Datastar signals/expressions only: on successful add, the modal stays open in a completed state, hides the form/actions, and keeps only an explicit close action while error flows remain in-place.
- Fixed Edit Permissions modal submit UX to guard against repeat submits and apply disabled styling/interaction lock to Cancel/Save while submitting.
- Fixed Edit Permissions success toast copy fallback to include member name from modal state when API response does not include first/last name.
- Fixed cascade add-member connection start timestamp generation in OrgMan `ConnectionService` to use point-in-time UTC (instead of local midnight), preventing day-off start-date drift in downstream MDP displays.
- Fixed additional legacy date generators in roster services to remove hardcoded timezone offsets and align to standardized time helpers:
  - `MembershipService::endPersonMembershipToday()` now uses MDP day-start UTC helper.
  - `ConnectionService::endRelationshipToday()` now uses MDP day-start UTC helper.
  - `GroupService::create_group_member()` now uses MDP day-start UTC helper for `start_date`.
  - `GroupService::remove_group_member()` now uses MDP day-start UTC helper when default end-date format is configured.
- Fixed cascade add-member behavior to be relationship-only:
  - removed direct membership-seat assignment call from `CascadeStrategy`.
  - added pre-add seat-capacity check (`active_assignments_count` vs effective max assignments).
  - downstream/system cascade automation remains responsible for membership assignment.

### Changed
- Updated organization summary card text/typography:
  - Label separators now use colons (`Membership Tier:`, `Membership Owner:`, `Renewal Date:`).
  - Organization name typography now uses rem-based `wt_text-lg` and `wt_font-bold`.
  - Missing renewal dates now render as `Renewal Date: Not set.`
- Updated organization summary card background token to use the requested color:
  - `--wicket-orgman-bg-summary-card: #DBE5FF`
- Wired summary card background to host theme accent-light token in the variable bridge:
  - `--wicket-orgman-bg-summary-card: var(--bg-accent-light, #DBE5FF)`
- Updated remove-policy callout email link affordance:
  - interactive link color
  - underline by default
  - no underline on hover
- Refined OrgMan account banner title spacing with scoped CSS variables and exact wrapper selector for account-center banner markup.
- Pagination controls in member/group results now render only when there is more than one page (no standalone `1` button on single-page result sets).
- Added explicit spacing between account-status icon and unconfirmed-status label in member cards.
- Reduced unconfirmed account-status label size to `0.8rem` using the new `wt_text-2xs` utility, and added missing utility declarations (`wt_ml-1`, `wt_text-2xs`) to the main stylesheet.
- Reduced member-card stack spacing in list views for denser roster scanning (`wt_gap-4` -> `wt_gap-1`).
- Reset Edit Permissions modal state on open/close (roles/success/submitting/message container) to prevent previous-user state and stale success/error messages from persisting.
- Updated Add Member and Edit Permissions success states to avoid auto-close and present a single `Close modal` action after success (form and primary/cancel actions hidden until modal is closed/reset).
- Fixed modal completion action visibility on initial open by using Datastar class toggles that keep `Close modal` hidden until success state is true.
- Applied the same completion-state UX to Remove Member confirmation modals (org/group): no auto-close on success, destructive form/actions hidden after success, and a single `Close modal` action.
- Switched modal completion visibility bindings to Datastar `data-show` (from underscore class toggles) to ensure `Close` buttons are hidden on initial load and only shown after success; button label updated from `Close modal` to `Close`.
- Hardened Edit Permissions role rehydration across modal opens by resetting member signals before assignment and binding checkbox checked-state to both member identity and role signals (prevents stale checkmarks when switching users or reopening).
- Fixed Edit Permissions Datastar click-expression parsing by safely hydrating `currentMemberRoles` with escaped JSON (`JSON.parse(...)`) instead of raw inline array literals.
- Updated Edit Permissions post-save client state to sync row-level role payloads for the edited member, so reopening the same modal reflects newly saved roles immediately.
- Improved Add Member submit button UX across org/group/unified modals with consistent disabled states while requests are in flight.
- Standardized modal submit loaders with new prefixed utilities (`wt_loader`, `wt_loader_button`) in library CSS, using OrgMan palette tokens; applied to `Add Member` and `Save Permissions` submitting states.
- Fixed standardized button-loader visibility by tuning button-specific loader geometry (ring width + inner inset) and explicit inline-block display for reliable rendering in submit buttons.
- Added subtle submit-state micro-animation for modal action buttons (`wt_button_submit_async`), with smooth label fade and centered loader entrance.
- Hardened modal retry/error paths so submit state always resets (spinner hidden, actions re-enabled) without forcing modal close; includes group add-member SSE signal patches.
- Fixed initial modal render state so submit loaders are hidden by default and only displayed when the submit button enters `wt_is-loading`.
- Fixed interactive text utility token reference: `.wt_text-interactive` now uses `--wicket-orgman-interactive` (removes undefined `--wicket-orgman-text-interactive` usage).
- Added regression coverage for standardized helper-backed group/member date payloads:
  - `GroupServiceTest` now asserts helper-based `start_date`/`end_date` payloads.
  - `MembershipServiceTest` now asserts helper-based membership `ends_at` payload.
- Moved remaining inline behavior/style from injected OrgMan templates into enqueued assets:
  - `content-organization-profile.php` demographics toggle logic now uses data attributes + `orgman-content-behaviors.js`.
  - `content-supplemental-members.php` GF seat-validation logic now uses data attributes + `orgman-content-behaviors.js`.
  - Supplemental-members visual styles now live in `modern-orgman-static.css` (scoped to `.wicket-orgman-supplemental`).
- Completed PSR-12 naming migration across internal class APIs:
  - service/strategy/helper/controller class methods now use camelCase.
  - snake_case compatibility wrappers were removed for internal APIs.
  - retained `OrgMan::get_instance()` as the only supported theme bridge alias.

### Documentation
- Added `docs/configs/CCHL.md` to document CCHL-only role overrides (`supplemental_member`, `CCHL Member Community`).
- Updated `docs/configs/MSA.md` with:
  - cascade feature-flag usage (`feature_flags.membership_resolution_prefer_current_cycle = true`)
  - explicit neutral auto-roles override (`member_addition.auto_assign_roles = []`)
  - hidden description field in add-member form.
- Updated `docs/CONFIGURATION.md` defaults/reference:
  - `member_addition.auto_assign_roles` default is `[]`
  - documented `feature_flags.membership_resolution_prefer_current_cycle`.
- Updated naming guidance docs (`AGENTS.md`, `README.md`, `docs/ARCHITECTURE.md`, `docs/BACKWARDS-COMPATIBILITY.md`) to document PSR-12 camelCase policy and the single `OrgMan::get_instance()` exception.

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
- Strategy-aware CSV processing via `MemberService->addMember(...)` per row, with additive-only behavior and duplicate-skip handling.
- Strategy-aware CSV processing via `MemberService->addMember(...)` per row, with additive-only behavior and duplicate-skip handling.

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
