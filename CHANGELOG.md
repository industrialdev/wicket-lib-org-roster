# Changelog

All notable changes to this project are documented in this file.

## [0.9.1] - 2026-05-08

### Changed
- Add-member duplicate check in `process/add-member.php` now delegates to `wicket_person_in_membership()` (with `function_exists` guard) instead of issuing an inline `/person_memberships/query` API call and manually inspecting `active`/`in_grace` attributes. Centralizes duplicate detection, removes ~50 lines of inline query/error-handling logic, and simplifies the control flow.

### Fixed
- Added `data-member-status` and `data-member-details` data attributes to member card elements in `members-list.php` for reliable Datastar element targeting.

## [0.9.0] - 2026-05-07

**Requires wicket-wp-base-plugin 2.4.0 or greater.**

### Changed
- Delegated person-organization connection and membership operations to wicket-wp-base-plugin helper functions, removing ~400 lines of duplicated API-client code:
  - `RelationshipHelper::get_available_relationship_types()` → `wicket_get_person_org_relationship_types()`
  - `RelationshipHelper::is_valid_relationship_type()` → `wicket_is_valid_person_org_relationship_type()`
  - `ConnectionService::personHasMembership()` → `wicket_person_has_membership()`
  - `ConnectionService::createPersonToOrgConnection()` → `wicket_ensure_person_org_connection()`
  - `ConnectionService::endRelationshipToday()` → `wicket_end_person_org_connection()`
  - `ConnectionService::getActivePersonOrgConnections()` → `wicket_get_active_person_org_connections()`
  - `PersonService::getOrCreatePerson()` → `wicket_ensure_person()`
  - `BulkMemberUploadService::resolveCsvHeaders()` → `wicket_csv_resolve_headers()`
  - Time helpers now use `wicket_time_get_current_iso8601_utc()` and `wicket_time_get_mdp_day_start_iso8601_utc()`.
- Duplicate member detection in `DirectAssignmentStrategy` and `CascadeStrategy` now returns an explicit `WP_Error` (`member_already_exists`) instead of silently skipping with a warning log. Add-member UIs will now surface duplicate errors to users.

### Fixed
- Removed `DateTimeImmutable` import from `ConnectionService` (no longer needed after delegating to base plugin time helpers).

## [0.8.8] - 2026-05-07

### Fixed
- Prevented duplicate membership seat assignment in `DirectAssignmentStrategy::addMember()`. When `personHasMembership()` returned true (person already had a membership), connection creation was skipped but `assignPersonToMembershipSeat()` still ran unconditionally, creating a second seat and consuming an extra seat. Seat assignment now only executes when the person does not already hold a membership, with a warning log on the skip path.

## [0.8.7] - 2026-05-06

### Fixed
- Backward compatibility for themes still referencing pre-0.8 namespace (`OrgManagement\OrgMan`) and filter names (`wicket/acc/orgman/*`). Added `class_alias` so old `use` statements resolve, and dual `apply_filters` calls for both new (`wicket/org-roster/*`) and legacy (`wicket/acc/orgman/*`) filter names. Added Composer `files` autoload entry (`src/compat.php`) that registers a prepend autoloader shim, ensuring `OrgManagement\OrgMan` resolves to `WicketORM\OrgMan` even before the PSR-4 autoloader is triggered. Themes no longer need patching when the library is updated.

## [0.8.5] - 2026-05-06

### Fixed
- Remove button is no longer shown for organization owner rows when viewed by non-owner users (membership managers, etc.). Previously the visibility check only hid the button when the owner viewed their own row, allowing other managers to see (and attempt) removal of the org owner.
- Added missing owner guard to group members list remove button (previously unguarded).
- Renamed `group-members-list.php` to `members-list-groups.php` for consistent naming with other member list templates.

## [0.8.3] - 2026-05-05

### Fixed
- Seat-capacity error message now reports the actual conflicting role slug (e.g. `observer`) instead of hardcoded `member`.
- Removed fatal crash during group member removal caused by invalid `clearMembersCache()` calls in `GroupsStrategy`; cache invalidation remains handled by `MembershipRosterWriter` after strategy execution.
- Eliminated base-plugin 404 warnings from non-UUID org identifiers by adding UUID guards before `wicket_get_organization()` lookups in `GroupService` (`resolveScopeTokens` path and org-name resolution loop).
- Resolved group-member create 422 errors (`schema does not match` and `#/name`) by aligning custom data payload semantics with working browser behavior:
  - roster flow now uses org UUID as custom data source when available (fallback to org identifier),
  - group create now runs through the shared helper path with custom data support.

### Changed
- `GroupService::createGroupMember()` is now helper-first (`wicket_add_group_member`) with direct API fallback only when the helper is unavailable.
- Extended base helper `wicket_add_group_member()` to accept optional `custom_data_field` argument while preserving previous defaults/behavior (`custom_data_field` remains `null` unless explicitly provided).

## [0.8.2] - 2026-05-04

### Fixed
- Group member cards disappearing from the DOM when `groups.presentation.use_unified_member_list` is enabled (default: `true`). Group members lacked `lazy_loaded=true`, causing the unified template to fire SSE `data-init` fetches. The SSE endpoint (`member-details.php`) queried `organization_memberships/{uuid}/person_memberships` — which does not contain group members — and the resulting `null` triggered `removeElements()` on the card container.
- Group member roles displaying as em-dash (`\u2014`). The singular `role` field from group member API data was not promoted to the `roles` / `current_roles` arrays that the unified template's role resolution logic checks first. Both entry points (`group-members.php` page-level render and `group-members-list-endpoint.php` Datastar fetch) now promote the role into both arrays.
- Group member emails blank. `GroupService::extractFilteredGroupMembers` and `GroupService::normalizeGroupMembersResponse` checked `attributes.email` and `attributes.primary_email` for the person's email address, but the Wicket API person serializer exposes the field as `primary_email_address`. Extraction now checks `primary_email_address` first, falling back to `email` then `primary_email`.
- Group member confirmed-status badges missing. `GroupService` now extracts `confirmed_at` from person `included` data in both member-extraction methods.
- N+1 API calls in `members-list-groups.php` (non-unified fallback). Each card previously called `MemberService::isUserConfirmed()` per member; now reads `confirmed_at` directly from the member data array.

### Added
- Defense-in-depth in `member-details.php`: when `mode=groups` and the member is not found in the org membership endpoint, the SSE fallback uses the person API (`getPersonById`) for confirmed status and email instead of removing the card from the DOM.
- `mode` and `group_uuid` forwarded to the lazy-details URL in `members-list-unified.php` so the SSE endpoint can identify group context.
- Lazy-details cache key in `member-details.php` now includes `mode` and `group_uuid` to prevent cache collisions between org-member and group-member lookups for the same person.

## [0.8.1] - 2026-04-30

### Added
- `GroupService::resolveManagerOrgAccess()`: resolves the org UUID and identifier for a person who holds the manager MDP role (e.g. `membership_manager`) scoped to an organization, by querying `/people/{uuid}/roles` directly.
- `GroupService::fetchRosterTaggedGroupsForOrg()`: fetches all groups for a given org UUID and filters to those tagged with the roster-management tag (respects `groups.matching.tag_name` / `tag_case_sensitive` config).
- `GroupService::checkManagerGroupAccess()`: fallback used inside `canManageGroup` — grants access when a person holds the manager MDP role and the target group carries the roster tag, returning the correct org scope for downstream filtering.
- `GroupService::applyManagerGroupFallback()`: fallback used inside `getManageableGroups` — appends "Roster Management" tagged groups for a manager's org when none were found via group-membership records. No-ops on non-`groups` strategy sites.

### Fixed
- `GroupService::canManageGroup()` and `getManageableGroups()` now correctly grant access to users whose management authority is an org-scoped MDP role (e.g. `membership_manager`) rather than a group member role (president, delegate, etc.). Previously both methods only checked `/group_members` records, so users with the MDP role but no group membership saw no groups and could not add, remove, or view roster entries.

## [0.8.0] - 2026-04-30

### Added
- `MembershipRosterReader`: new internal read-focused module that owns non-groups membership roster reads — normalized fetch, fallback search chain, enrichment, member row shaping, and single-member SSE lookup — extracted from `MemberService` (Phase 1).
- `MembershipRosterWriter`: new internal write module that centralizes membership update orchestration — role updates, relationship-type updates, description updates, and add/remove member flows — extracted from `MemberService` (Phases 4–5).
- Membership generation included in search cache keys (`MembershipRosterReader`), so search results auto-invalidate when membership generation is bumped — no more stale search results after mutations (Phase 3).
- Centralized group mutation policy in `GroupsStrategy`: group add/remove policy validation now lives in the strategy itself, thinning the `add-group-member.php` and `remove-group-member.php` process handlers (Phase 6).
- `ROSTER-CORE-REFACTOR-RFC.md` and `ROSTER-REFACTOR-PHASE0-NOTES.md`: engineering documentation for the phased deep-module refactor plan and baseline contract capture.
- Planned CSAE org-roster configuration documentation (`docs/engineering/configs/CSAE.md`, `docs/guides/ACTIVE-SITES.md`).

### Changed
- `MemberService` reduced from ~810 lines to a thin compatibility facade (~355 lines) delegating reads to `MembershipRosterReader` and writes to `MembershipRosterWriter`.
- `MembershipRosterWriter` now owns strategy initialization and cache invalidation for `addMember`/`removeMember`, removing duplicated orchestration from `MemberService` and process handlers.
- Search cache keys now include membership generation alongside org UUID, search term, page, and size — matching the invalidation pattern already used for list caches.
- Group add/remove process handlers (`add-group-member.php`, `remove-group-member.php`) no longer contain policy orchestration; `GroupsStrategy` handles duplicate checks, seat-capacity guards, and ownership protection directly.
- IAA site config updated: added `access.roles.manager`, `groups.roles.roster`, `groups.roles.seat_limited`, `groups.removal.mode`, and reorganized override structure.
- Dead code removed from `MemberService` after Phase 7 cleanup: redundant strategy initialization, inline mutation methods, and duplicated cache-clearing choreography.

### Fixed
- Fixed `MembershipRosterReader` service injection: reader now correctly receives its dependencies after Phase 7 dead-code cleanup resolved a wiring regression.

## [0.7.3] - 2026-04-28

### Changed
- `members-list-unified.php` is now the default member list template. No configuration required.
- `presentation.member_list.use_unified` removed. Replaced by `presentation.member_list.use_legacy_list` (default `false`). Set to `true` to opt into the legacy `members-list.php` template temporarily.
- `OrgManConfig` default for `presentation.member_list` updated accordingly.

## [0.7.2] - 2026-04-27

### Fixed
- Fixed Chrome-only member-search request failures (`ERR_CONNECTION_CLOSED`) caused by Datastar serializing DOM refs into the `datastar` GET query payload. Removed `data-ref` usage from search-adjacent member management templates so element refs are no longer transmitted in URL query signals.
- Replaced ref-signal dependent handlers with DOM-safe reset/clear logic (`el.reset()` and `document.getElementById(...)`) across:
  - `templates-partials/members-view-unified.php`
  - `templates-partials/organization-members.php`
  - `templates-partials/group-members.php`
  - `templates-partials/members-list.php`
- Fixed non-unified members-list modal actions to avoid dangling ref-signal usage after ref removal:
  - Add Member button no longer calls `$addMemberForm.reset()`
  - Edit Permissions/Remove actions no longer rely on `$updatePermissionsMessages` / `$removeMemberMessages`

## [0.7.1] - 2026-04-24

### Added
- `Helper::should_show_member_name()`, `should_show_member_email()`, and `should_show_member_relationship_type()` — new per-card visibility helpers reading `presentation.member_card.fields.*` config keys that were previously defined but never consumed.
- `should_show_member_relationship_type()` gates on both `member_card.fields.relationship_type.enabled` and `presentation.relationships.show_type` (AND logic) so both config knobs remain meaningful.

### Changed
- All five member card templates (`members-list.php`, `members-list-unified.php`, `member-details.php`, `members-list-groups.php`, `members-view-unified.php`) now guard `name`, `email`, and `relationship_type` rendering through the new helpers, replacing the old `!should_hide_relationship_type()` calls and unconditional output.
- `MembershipService::getMembershipForOrganization()` and `getOrgMembershipData()` converted from raw `get_transient`/`set_transient` to `CacheService`, giving their cached values salt-versioned keys that expire automatically on `cache_salt` bumps.
- `MemberService::setCachedData()` now accepts an optional `?int $duration` parameter, passed through to `CacheService::set()`.
- Search results in `MemberService` are now cached using `platform.cache.search_clear_cache_duration` as TTL (key scheme: `orgman_search_<md5>`), wiring a config key that was previously defined but never consumed.
- Removed unused `show_special_types` key from `presentation.relationships` config.

### Fixed
- `members-list.php`: `OrgHelpers\Helper\should_show_member_roles()` corrected to `OrgHelpers\Helper::should_show_member_roles()` — backslash namespace separator instead of `::` would have caused a fatal error at runtime when rendering roles.

## [0.7.0] - 2026-04-24

### Added
- `CacheService`: new versioned cache layer with salt-based global invalidation and automatic cleanup of legacy transient keys, replacing ad-hoc transient management across `MemberService`, `GroupService`, and `MembershipService`.
- Lazy-loading of member detail cards via Server-Sent Events (SSE): member cards now render immediately as skeleton placeholders and hydrate via a dedicated `member-details` SSE endpoint. New `MemberService::getMemberByPersonUuid()` fetches and normalizes a single member by person UUID for per-card detail delivery.
- Per-membership cache generation (`CacheService::getMembershipGeneration()` / `bumpMembershipGeneration()`): O(1) member-list cache invalidation by bumping a generation counter instead of deleting individual page/size transients. `invalidateMemberCache` now calls `bumpMembershipGeneration` and retains legacy deletion for transition.
- Pre-warming of per-member lazy-details cache during full member list loads, improving cache-hit rate on subsequent SSE requests.
- Skeleton loader CSS (`.wt_skeleton` and helpers) for member cards in `modern-orgman-static.css`.
- Disabled-state styles for primary buttons (`.wicket-orgman .button.button--primary.button--disabled`).
- `presentation.member_list.page_size` config flag: page size is now driven from config instead of a hard-coded value; preloads additional `presentation.member_list` flags in the organization-members template.
- `confirmed_at` now sourced from included `users` resources: `MemberService` builds a `userIndex` (keyed by `person_id`) from the API `included` payload so user-level timestamps are available when the person record alone does not carry them.
- Engineering documentation: `docs/engineering/performance-migration-plan.md` covering the migration from legacy transient caching to `CacheService`.

### Changed
- Role display now uses `attributes['name']` instead of `attributes['slug']`; ad-hoc UUID-suffix stripping logic removed from `MemberService` and `PermissionService`.
- Membership generation appended to lazy member cache keys so cached lazy-detail fragments expire correctly when the roster changes.
- Default page size standardized to 15 across `OrgManConfig`, `GroupService`, `MemberService`, and `MembershipService`.
- Datastar updated to v1.0.1 (CDN URL now uses the `@v` tag).
- `TemplateHelper` no longer forces a `Content-Type` header, allowing SSE templates to set `text/event-stream` themselves.
- `members-list.php` and `members-list-unified.php` updated to emit SSE-compatible output, use consistent `member-card-<id>` element IDs, and trigger lazy detail fetches via `data-effect` intersection guards instead of `data-on-load`.
- Logging standardized to the Wicket logger (`\Wicket()->log()`) with a `source: wicket-orgman` context key across all member-related services and templates; verbose debug/info statements removed to reduce log noise.
- Non-critical log conditions (missing optional functions/classes, empty API responses, JSON decode failures, skipped auto-opt-ins, filtered member cards) demoted from `warning` to `info` level.
- `members-list-unified.php` and `members-list.php` now build lazy-details URLs via `add_query_arg` / `home_url` and register required `query_vars` and `parse_request` handlers in `TemplateHelper` so hypermedia requests are handled earlier in the WP request lifecycle.

### Fixed
- Organization ID comparison now uses `trim()` to avoid false-negative mismatches caused by stray whitespace in API-returned IDs.
- Orphaned member cards (members filtered out during a full SSE load) are now explicitly removed from the DOM via `deleteFragments` instead of being left as empty placeholders.

## [0.6.2] - 2026-04-21

### Added
- Membership ownership detection: users who own organization memberships (via the owner relationship on organization_memberships) are now recognized and granted access, even without direct membership entries or org-scoped roles. New `OrganizationService::getUserOrganizationsFromOwnership()` method with pagination support for discovering owned organizations.
- Enhanced `PermissionHelper::has_active_membership()` to check membership ownership before returning false when no direct memberships are found.

### Changed
- Member list pagination controls now only render when total items exceed zero, removing empty pagination markup from no-results states.
- Fixed button utility class naming in `members-list-unified.php` (hyphenated `wt_py-2` → underscored `wt_py_2`) and added `wt_justify-center` for consistent button alignment.

## [0.6.1] - 2026-04-21

### Changed
- Templates now use `get_component('alert')` and `get_component('button')` from the base plugin instead of custom markup, standardizing UI components across `engagement-summary.php`, `export-members-status.php`, `members-list-unified.php`, and `organization-list.php`. Visual classes and Datastar attributes are forwarded via the `atts` parameter so reactive behavior is preserved.

### Fixed
- Remove-policy callout in `members-list-unified.php` was rendered unconditionally; it now only outputs when `$show_remove_policy_callout` is true **and** `$remove_policy_callout_placement === 'below_members'`, preventing it from appearing in unintended locations.

## [0.6.0] - 2026-04-17

### Added
- MemberExportService: Async, batch-processed CSV export of all org members with WP-Cron scheduling, secure download tokens, and expiration controls.
- EngagementService: MDP engagement/donation data display with configurable sections, badge parsing from person tags, and per-field formatting (currency, date, yesno, string).
- New REST endpoints: `/org-management/v1/exports/initiate`, `/org-management/v1/exports/status`, `/org-management/v1/engagement/person`.
- New templates: `export-members-modal.php`, `export-members-status.php`, `process/initiate-member-export.php`, `engagement-summary.php`.
- Config section `exports`: Master switch, batch size, token expiration days, max downloads, upload dir slug, and configurable CSV columns.
- Config section `engagement`: Master switch, member org UUIDs for active membership checks, person/org data field keys, and configurable section definitions with fields and badge patterns.
- Full QA test coverage: 16 tests for MemberExportService, 18 tests for EngagementService in `qa/tests/Unit/Roster/Services/`.

### Changed
- OrgManConfig: Added `exports` and `engagement` top-level config sections (both `enabled => false` by default).
- OrgMan: Conditional service/controller initialization for exports and engagement based on config flags.
- OrgMan: Added cron hooks for export processing and cleanup, plus download query var registration.
- WP shims: Added namespace-scoped WordPress function shims in `WicketORM\Services\` for isolated unit testing.

## [0.5.30] - 2026-04-13

### Fixed
- Fixed `MemberService::update_member_roles()` calling `wicket_remove_role()` without passing `$orgUuid`, causing role removal to be unscoped; now passes the org UUID as the third argument.
- Added post-removal verification in `MemberService::update_member_roles()`: after `wicket_remove_role()` reports success, re-fetches the person's current roles and returns a `WP_Error` (`role_remove_verify_failed`) if the role is still present, preventing silent partial failures.

## [0.5.29] - 2026-04-10

### Changed
- Normalized org member-list refresh logic across add-member, remove-member, and update-permissions flows to a shared server-side patch generation path.
- Updated add-member process success handling to build list refresh patches via the shared helper, matching the remove/edit-permissions refresh mechanism.
- Kept edit-permissions success handling server-driven (no client-side `@get` list refetch) while preserving existing modal success state behavior.

### Added
- Added `WicketORM\Helpers\MemberListRefresh::buildOrgMembersListPatches()` to centralize Datastar members-list patch generation for org roster mutations.
- Added hidden `org_dom_suffix` form posting in legacy and unified member views for edit-permissions and remove-member forms to ensure refresh patches target the exact list container.
- Added QA regression coverage in `qa/tests/Unit/Roster/Helpers/MemberListRefreshRegressionTest.php` to guard the shared helper path and server-driven edit-permissions refresh wiring.

## [0.5.28] - 2026-04-10

### Fixed
- Fixed `DirectAssignmentStrategy`, `CascadeStrategy`, and `MembershipCycleStrategy` incorrectly assuming `getOrganizationOwner()` returns an object; Wicket API responses can be arrays, causing owner-removal protection to silently never trigger.
- Fixed `PermissionService::removePersonSingleRoleFromOrg()` assuming `wicket_get_person_by_id()` returns an object; role ID lookup now handles both array and object shapes. Added fast path via `wicket_remove_role()` when available.
- Fixed `DirectAssignmentStrategy::sendAssignmentEmail()` unconditionally overwriting `$to` with a raw object property access after the fallback-email logic had already resolved it, negating the fallback entirely.
- Fixed `TouchpointService` assuming `wicket_get_person_by_id()` returns an object; field extraction now handles both array and object responses.
- Fixed `DirectAssignmentStrategy::removeMember()` calling `wicket_remove_role()` without a `function_exists()` guard, causing a fatal error when the Wicket base plugin is inactive.
- Fixed `MembershipService::getMembershipForOrganization()` and `OrganizationService::getUserOrganizations()` calling `wicket_current_person_uuid()` without a `function_exists()` guard; both now return early cleanly when the function is unavailable.

## [0.5.27] - 2026-04-10

### Fixed
- Fixed REST routes never registering: `OrgMan::registerApiRoutes()` was checking for `register_routes()` instead of `registerRoutes()`, so no API endpoints were ever registered.
- Fixed REST permission callbacks silently falling back to public access: `BusinessInfoController` and `DocumentController` referenced `check_logged_in` instead of `checkLoggedIn`, bypassing authentication on all their endpoints.
- Fixed `NotificationService::error()`, `warning()`, and `info()` throwing fatal errors by calling the non-existent `add_notification()` instead of `addNotification()`.

## [0.5.25] - 2026-04-09

### Changed
- Changed additional seats to opt-in by default: `integrations.additional_seats.enabled` now defaults to `false` in `OrgManConfig`.
- Updated additional seats enabled fallbacks to default `false` in `ConfigService` and `ConfigurationController` filter/accessor methods.

## [0.5.23] - 2026-04-08

### Added
- Added configurable organization owner removal prevention across all roster management strategies (Direct, Cascade, Groups, MembershipCycle).
- Added `access.permissions.prevent_owner_removal` config flag (default: false) to enable blocking removal of organization owners.
- Added `access.permissions.owner_removal_requires_membership_owner_role` config flag (default: false) to require the owner to currently hold the `membership_owner` role before blocking removal.
- Added `CSAE.md` site configuration mirror documenting the CASAE roster implementation.

### Changed
- Unified organization owner removal logic in `remove-member.php` template to apply consistent guards across all strategies.
- Added lazy `PermissionService` instantiation to `CascadeStrategy`, `DirectAssignmentStrategy`, `GroupsStrategy`, and `MembershipCycleStrategy` for role validation during owner removal checks.
- Restructured documentation hierarchy: created `product/`, `engineering/`, and `guides/` directories; replaced `AGENTS.md` with standardized documentation rules; added `index.md` entry point.

## [0.5.20] - 2026-04-06

### Fixed
- Fixed org-scoped edit-permissions role updates to respect permissions modal allow/deny config so hidden roles are not removed.
- Fixed cascade strategy stale-relationship repair to preserve protected relationship types during the end-date operation.
- Fixed cascade strategy stale-relationship repair logging to include resolved relationship type and description.
- Enhanced connection service logging with detailed diagnostic tracking for active person-org connection lookups and end-date operations.
- Enhanced member service role management to normalize and filter manageable roles based on modal allow/deny configuration.

## [0.5.15] - 2026-03-25

### Fixed
- Fixed Edit Permissions role-diff logic to compare against organization-scoped roles only, preventing false no-op updates when the same role exists on a different organization.
- Updated role mutation flow to fail explicitly when role add/remove calls fail, instead of continuing with a false-success outcome.

### Added
- Added diagnostic logs to the update-permissions flow and `MemberService::updateMemberRoles()` for submitted/filtered roles, membership UUID resolution, role-diff decisions, per-role add/remove attempts, and failure/success outcomes.

## [0.5.14] - 2026-03-25

### Fixed
- Hardened add-member duplicate checks to fail closed when duplicate verification cannot be completed, preventing ambiguous re-add attempts.
- Treated `in_grace` person-membership records as existing during add-member duplicate checks.
- Hardened person lookup before creation to use a fallback API email search when helper lookup misses, reducing duplicate person creation when matching emails are not primary.

## [0.5.12] - 2026-03-25

### Added
- Added `AdditionalSeatsService::getAdditionalSeatsSetupIssues()`: performs prerequisite checks for the additional seats feature (WooCommerce product SKU, Gravity Form slug mapping, `supplemental-members` my-account page) and returns structured issue descriptors.
- Added implementer setup warning callout in `members-view-unified.php` and `organization-members.php`: visible to WordPress administrators only, rendered independently of org role so implementers see it regardless of membership permissions. Lists each missing prerequisite with actionable instructions.
- Copyable token chips in the setup warning: SKUs (`additional-seats`, `corporate-seats`), the Gravity Forms slug, and the my-account page slug are rendered as clickable `<code>` elements. Clicking copies the value to the clipboard and displays a "✓ Copied!" confirmation inline for 1.5 seconds.
- Guarded `getPurchaseFormUrl()` behind `canPurchaseAdditionalSeats()` to avoid unnecessary Wicket API calls and user-meta side effects for users without the purchase role.

### Fixed
- Fixed missing space between member name and "from this organization/group?" in the Remove Member confirmation modal across `members-view-unified.php`, `members-list.php`, and `group-members.php`.
- Fixed missing space after "Seats assigned:" and "Number of assigned people:" labels in the seat summary row in `members-list-unified.php`.

## [0.5.11] - 2026-03-23

### Fixed
- Fixed unified org-mode Add Member modal success state to render the success message (`$addMemberSuccessMessage`) instead of showing an empty modal with only the Close button.
- Fixed unified org-mode Add Member error handling to explicitly reset modal success signals (`addMemberSuccess`, `addMemberSuccessMessage`, `autoCloseCountdown`) on all server-side error responses, ensuring validation/permission/API errors remain visible instead of collapsing into an empty success-state modal.

## [0.5.10] - 2026-03-23

### Fixed
- Rewrote `sync-orgman-lib.php` swap logic to use a near-atomic copy-then-replace strategy: the new library is fully copied from vendor under a temporary name while the existing copy remains live, the old copy is then deleted, and the new copy is renamed into the final path in a single near-atomic operation. Eliminates leftover backup directories and minimises the downtime window during sync.
- Replaced manual recursive `scandir` deletion with `RecursiveDirectoryIterator` + `CHILD_FIRST` traversal, fixing failures on directories containing hidden files (e.g. `.DS_Store`).

## [0.5.6] - 2026-03-20

### Fixed
- Fixed organization details action button layout to properly stretch and center content across different container widths.
- Added `.wt_flex-equal` utility class with consistent flex behavior and `min-width: 0` to prevent overflow issues.

### Added
- Added legacy ACC slug compatibility to OrgMan content routing: `org-management`, `org-management-profile`, `org-management-members`, and `org-management-roster` now map to their respective templates for backwards compatibility with existing Account Centre installations.

## [0.5.4] - 2026-03-19

### Fixed
- Fixed group members pagination: the API paginates before org-scope filtering, which could leave sparse first pages when unrelated memberships occupy the raw page window. Now fetch all pages, filter locally by org scope, then paginate the filtered set.

### Added
- Added `$listLoading` signal with visible loading spinner for member list pagination. Applied to `members-list.php`, replacing the old `$membersLoading` state management pattern.
- Added `access.roles.descriptions.*` config option for role descriptions shown alongside role checkboxes in Add Member and Edit Permissions modals.
- Added `member_management.forms.add_member.clear_form_on_error` config option.

### Changed
- Refactored `GroupService::getManageRoles()` to use `getFilteredGroupMembersPage()` which fetches all pages locally then filters and paginates.
- Replaced `data-attr:disabled="$membersLoading"` with proper `$listLoading` state and Datastar loading indicators across pagination controls.
- Organization details template now only fetches membership data in non-groups mode, avoiding unnecessary API calls when viewing groups.

### Documentation
- Updated `CONFIGURATION.md` with new role descriptions config and clear_form_on_error option.
- Updated `AGENTS.md` with Datastar interactivity guideline.

## [0.5.3] - 2026-03-19

### Fixed
- Prevented all modals (add member, remove member, edit permissions, bulk upload) from being dismissed by clicking the backdrop. Removed `data-on:click__outside__capture` handlers across `members-view-unified.php`, `group-members.php`, and `organization-members.php`; modals now close only via their X or Cancel/Close buttons.
- Disabled the modal X (close) button while a server request is in-flight, matching the existing Cancel/action button behavior. Applied `wt_pointer-events-none`, `wt_opacity-50`, and `aria-disabled` bound to the relevant `*Submitting` signal on all 10 modal close buttons across `members-view-unified.php`, `group-members.php`, `organization-members.php`, and `members-list.php`.

### Added
- Extended the add-member auto-close-on-success feature to org-level (non-group) flows via two new config keys under `presentation.member_view`:
  - `add_member_auto_close_on_success` (default `false`)
  - `add_member_auto_close_delay_seconds` (default `7`)
- Added a visible countdown message inside the add-member modal success state when auto-close is enabled. The message displays "This dialog will close automatically in N seconds." with a live-updating second counter, giving users clear feedback before the modal dismisses itself.

### Changed
- Refactored the add-member auto-close countdown from imperative JS (`setTimeout`/`setInterval` + DOM queries) to idiomatic Datastar using `data-on-interval__duration.1000`, `data-text`, and `data-show` bound to an `autoCloseCountdown` signal. Eliminated all manual timer management (`orgmanAutoCloseTimer`, `orgmanAutoCloseInterval`) and DOM element lookups from the auto-close logic.
- Replaced IIFE form-reset patterns (`getElementById` + `querySelector('form').reset()`) with `data-ref="addMemberForm"` + `$addMemberForm.reset()` across all add-member modals in `members-view-unified.php`, `group-members.php`, `organization-members.php`, and `members-list.php`.
- Replaced `data-effect="el.checked = $currentMemberRoles.includes('...')"` IIFEs on role checkboxes with declarative `data-attr:checked` in `members-view-unified.php` and `members-list.php`.
- Replaced `data-effect="el.value = $signal"` on relationship-type selects and description inputs/textareas with `data-bind` two-way binding in `members-view-unified.php` and `members-list.php`.
- Replaced `getElementById('...').innerHTML = ''` IIFEs for clearing message containers with `data-ref` + `$ref.innerHTML = ''` across edit-permissions and remove-member flows in `members-view-unified.php` and `members-list.php`.

## [0.5.2] - 2026-03-18

### Added
- Added `member_management.removal.direct.preserve_relationship` config option so direct strategy can optionally keep the org relationship active and only strip roles on member removal.
- Added `ConnectionService::endRelationshipAtActionTime()` to end relationships at the precise action time instead of end-of-day.
- Added server-driven member list refresh after remove: the remove-member SSE response now includes a Datastar element patch that re-renders the members list, replacing the client-side `@get()` refetch.

### Fixed
- Prevented double-submission on remove-member and bulk-upload forms with `if(!$submitting)` guards.
- Fixed stale/ended group memberships appearing in `GroupService::getManageRoles()` by post-filtering results through `isGroupMembershipActiveRecord()` when requesting active records.
- Fixed remove-member modal retaining previous success/error state when reopened by resetting signals and clearing the messages container on button click.
- Extended add-member modal reset selector to also clear `#group-member-messages`.

### Changed
- Replaced inline SVG spinners with the CSS-based `wt_loader` / `wt_button_submit_async` pattern across remove-member and bulk-upload buttons and switched from `data-attr:disabled` to `aria-disabled` with pointer-events.

### Documentation
- Documented `member_management.removal.direct.preserve_relationship` in `CONFIGURATION.md` and `direct-add-remove.md`.

## [0.5.1] - 2026-03-17

### Fixed
- Fixed group-strategy member removal so successful remove actions now drive modal success state and refresh the rendered group member list from the remove endpoint response.

### Added
- Added shared `removal.end_date_anchor` config with default `action_time`, plus strategy-level override support for membership-cycle, relationships, and groups end-dating flows.

### Documentation
- Updated configuration documentation and the stored IAA site config reference to reflect shared removal anchor defaults and the explicit IAA group-removal override.

## [0.5.0] - 2026-03-17

### Changed
- Reorganized the library configuration schema around canonical sections: `access`, `membership`, `relationships`, `member_management`, `groups`, `presentation`, `integrations`, and `platform`.
- Migrated runtime config consumers across templates, services, helpers, and strategies to the canonical config paths.
- Updated active site override files to the canonical config shape while preserving their current site-specific values.

### Fixed
- Restored legacy config-path fallbacks in member list and update-permissions flows so existing regression coverage continues to pass during the config migration.
- Removed undefined site override keys from active-site configs and matching site configuration docs.
- Prevented duplicate group-member additions for the same person, role, group, and organization scope in groups strategy, returning a `group_member_exists` error instead of creating a second active assignment.
- Preserved legacy groups seat-limit config fallback while honoring the canonical `groups.roles.seat_limited` path during the config migration.
- Updated group add-member success handling so the modal can remain in a success state for an optional delayed auto-close instead of forcing an immediate close.

### Added
- Added `.ci/update-active-sites.sh` and the Composer shortcut `composer update:active-sites` to update `industrialdev/wicket-lib-org-roster` across all known active site Composer roots under `site-folder/src/`.
- Added configurable group add-member modal auto-close controls:
  - `groups.presentation.add_member_auto_close_on_success` (default `false`)
  - `groups.presentation.add_member_auto_close_delay_seconds` (default `7`)
- Added legacy-path compatibility for the same group add-member modal auto-close controls under `groups.ui.*` during the config migration.

### Documentation
- Rewrote configuration documentation around the canonical schema and added a dedicated reorganization plan with migration mappings.
- Updated `docs/configs/*.md` so the active-site config docs mirror the real `org-roster.php` overrides as the source of truth.
- Updated the stored IAA config reference to match the renamed `org-roster.php` site override and document its enabled 7-second delayed group add-member auto-close behavior.

## [0.4.16] - 2026-03-16

### Changed
- Centralized touchpoint logging into a dedicated `TouchpointService` to standardize how member additions and removals are recorded in the MDP.
- Expanded touchpoint coverage to include all roster management strategies (Cascade, Groups, and Membership Cycle), ensuring consistent activity tracking across the library.
- Refactored `DirectAssignmentStrategy` to use the new `TouchpointService`.

## [0.4.15] - 2026-03-13

### Fixed
- Prevented duplicate owner-role assignment by deduplicating owner role handling in roster/member flows.

## [0.4.14] - 2026-03-12

### Fixed
- Updated Add Member success handling for org and group roster flows to refresh page 1 of the background members list in the same Datastar SSE response that renders the modal success state.
- Removed the temporary client-side post-success refresh orchestration for add-member flows after confirming the more reliable approach is a server-driven `datastar-patch-elements` update for the list container.

### Changed
- Extended `DatastarSSE::renderSuccess()` to support optional additional element patches in the same SSE stream, allowing modal/process handlers to update related page regions without a second request.
- Expanded `.ci/sync-orgman-lib.php` from Bedrock-only behavior to support both Standard WordPress (`wp-content/libs/...`) and Bedrock (`web/app/libs/...`) public runtime sync targets.

### Documentation
- Updated frontend/spec/testing docs to describe the current Add Member success flow: success message remains in the modal while the members list is patched server-side in the same SSE response.
- Updated installation/runtime docs to recommend `libs/` runtime copies for both Standard WordPress and Bedrock, with root `vendor/...` treated as the source for sync rather than the preferred deployed runtime location.

## [0.4.12] - 2026-03-11

### Fixed
- Standardized runtime write timestamps across roster services to use point-in-time UTC values from the base-plugin time helpers instead of day-boundary timestamps:
  - `ConnectionService::endRelationshipToday()` now writes the action instant for connection `ends_at`.
  - `MembershipService::endPersonMembershipToday()` now writes the action instant for membership `ends_at`.
  - `GroupService::createGroupMember()` now writes the action instant for group member `start_date`.
  - `GroupService::removeGroupMember()` now writes the action instant for group member `end_date` when using the canonical default format.
- Removed the temporary cascade stale-relationship end-date retry workaround after restoring canonical instant-based connection end-dating.
- Removed the undocumented legacy `groups.removal.end_date_format = 'Y-m-d\\T00:00:00P'` special-case from groups removal handling.

### Documentation
- Documented that `groups.removal.end_date_format` should remain `Y-m-d\\TH:i:s\\Z` unless a site explicitly requires a different API format.
- Updated installation and configuration examples to call out the canonical groups end-date format and base-plugin helper behavior.

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
  - New service: `WicketORM\Services\BulkMemberUploadService`.
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
- Added background processing wiring in `WicketORM\OrgMan`:
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
- Initial Composer library packaging for `industrialdev/wicket-lib-org-roster` with `WicketORM\OrgMan` singleton entrypoint.
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
