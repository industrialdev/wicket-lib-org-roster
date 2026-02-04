# Org Roster Groups Strategy Specs

Status: Draft (implementation aligned with unified view)
Owner: TBD
Date: 2026-02-02

## Objective
Implement a third org roster strategy: groups.
Use MDP groups to hold org members roster.
Strict backward compatibility required: no breaking changes to existing library behavior, methods, or public interfaces.

## Implementation Status (as of 2026-02-02)
Completed
- Groups strategy wiring, services, templates, and process endpoints are in place.
- Group list and member list UIs exist with pagination and search.
- Role-based access checks and roster-role validation are implemented.
- Removal supports end-date and delete modes (configurable).
- Groups config block added (tags, roles, seat limits, additional_info, UI edit fields).
- Unified members view (search + list + pagination + modals + seats callout) is the default for all strategies.
- Legacy members list/view preserved behind config flags for per-site fallback.

Open / Needs confirmation
- Org identifier key/format in custom_data_field must be confirmed and aligned with MDP data.
- Role identifiers are still config-driven, not derived from MDP responses.
- Seat-limit enforcement for member role only checks first 50 members.
- Group list sorting requirements not defined/implemented.
- Unified view search UX constraints (submit-only search) are enforced; verify if any auto-search is desired.

## Sources
- Legacy reference: legacy-templates-reference/legacy-reference-only/orgman-groups
- Legacy API patterns: docs/mdp-bruno (Bruno request collection for MDP)
- Ticket requirements: provided by requester (pasted below)
- External requirements doc: Google Doc (confirmed identical to ticket text; no additional content)

## Ticket Requirements (verbatim from requester)
### 1. Organizational context
- IAA has organization members representing associations.
- Each organization may participate in one or more groups that are designated for roster management.
- Groups eligible for roster management are identified in the MDP by the "Roster Management" tag.

Status
- [x] Tag-based group filtering implemented (configurable tag name + case sensitivity)

### 2. Eligible roles for managing rosters
Only users with one of the following group roles may manage their organization's roster:
- President
- Council Delegate
- Council Alternate Delegate
- Correspondent
- Council Representative/President (this is redundant, roles already listed above)

These roles:
- Are assigned directly by IAA staff in the MDP.
- Include additional info identifying the user's organization, which determines:
  - Which organization's roster the user can access
  - Which roster entries are visible and editable to them

If a user loses one of these roles, they immediately lose access to roster management.

Status
- [x] Manage roles enforced via config + access checks

### 3. Scope of roster access
- Users can view and manage only roster entries belonging to their organization.
- Organization membership is determined by matching the group role additional info between:
  - The managing user's role (President, Delegate, etc.)
  - The roster member's role
- Users cannot view or manage roster entries for other organizations within the same group.

Status
- [x] Group member list filtered by org identifier from custom_data_field
- [ ] Org identifier key/format confirmed and aligned with MDP data

### 4. Roster roles and limits
Each applicable group supports the following roster roles:
- Member
- Observer

Rules:
- Each organization may have exactly one "Member" per group.
- Organizations may have unlimited Observers per group.
- The one-Member rule is enforced per group, not globally across groups.

Status
- [x] Roster roles configured (member/observer)
- [x] Seat-limited roles enforced in add flow (one per org per group)
- [ ] Seat-limit enforcement currently checks first 50 members only (needs confirmation/adjustment)

### 5. Roster management capabilities
Authorized users (President, Delegate, Alternate Delegate, Correspondent) can:
- View their organization's roster for each applicable group
- Add:
  - One Member (if the seat is available)
  - Any number of Observers
- Remove Members or Observers from their organization
  - Removal is handled by end-dating the group role
- Replace the single Member
  - This is done by removing the existing Member and adding a new one

Restrictions:
- Users cannot edit details of existing roster entries (only add or remove)
- Users cannot remove or modify:
  - President
  - Delegate
  - Alternate Delegate
  - Correspondent roles
- Users cannot remove themselves from these managing roles
- Users may add themselves as a Member or Observer if applicable

Status
- [x] View/manage group roster per org implemented
- [x] Add Member/Observer implemented with roster role validation
- [x] Remove Member/Observer implemented via end-date (configurable)
- [x] Managing roles removal blocked
- [ ] Explicit self-removal prevention for managing roles not separately enforced (covered by role block)
- [x] Unified members view used for groups (search + list + pagination + modals + seats callout)

### 6. Automatic organization assignment
- When a user adds a new Member or Observer:
  - The group role additional info is automatically populated
  - It matches the organization associated with the managing user's role
- This ensures roster entries are always correctly tied to the organization.

Status
- [x] custom_data_field is set from managing user org identifier
- [ ] Org identifier key/format confirmed and aligned with MDP data

## Unknowns / Missing Requirements
- How to map organization identifier in role additional info (key and format).
- Definition of "group" source: MDP groups endpoint and filters (confirm via mdp-bruno).
- Pagination, search, sorting requirements (group list + member list).
- Required audit logging and notifications (email/alerts).

## Interview Answers (captured)
- Google Doc is identical to the ticket requirements above; ignore external link.
- Use docs/mdp-bruno collection for MDP endpoints; also reuse legacy reference patterns where sensible.
- Strategy UI: group listing should be one-step (no deep navigation). Show groups user can access AND that are attached to an organization.
- Filter for roster groups via tags: filter[tags_name_eq]=Tag%20Name
- Tag value: "Roster Management" (case-sensitive as shown in MDP data).
- Roles Member/Observer can exist across multiple groups for the same person.
- Role type identifiers should be derived from MDP API responses where possible; avoid hard-coding if feasible.
- Seat availability: follow existing direct/cascading strategy rules and callouts, including addl seats purchase CTA.
- Removal should be soft (end-date). This must be configurable; default to end-date at removal time.
- No additional permission overrides; no data migration.
- New requirement: every feature/sub-feature of groups strategy must be configurable via the same config array and filters used for existing strategies (role lists, tag name, removal behavior, etc.).
- Pagination: group list ideally paginated, but ok to cap to a configurable default (e.g., 20) if not practical; member list must be paginated like existing strategies.
- End-dated roles: exclude from member list UI (treated as removed for front-end).

Status
- [x] One-step group listing implemented
- [x] Tag filter implemented, configurable
- [x] Additional seats CTA reused for group roster list
- [x] Removal end-date configurable (end_date/delete)
- [x] Groups config block added to main config array
- [x] Member list pagination implemented
- [x] Group list pagination implemented (page size configurable)
- [x] End-dated roles excluded via active=true queries
- [ ] Role identifiers derived dynamically from MDP responses (currently config-driven)
- [x] Unified members view used by default (config: ui.member_view.use_unified)
- [x] Unified members list used by default (config: ui.member_list.use_unified)
- [x] Group UI uses unified view/list by default (config: groups.ui.use_unified_member_view/use_unified_member_list)

## Navigation / UX Requirements
- Org management landing page: list of eligible groups (user-accessible + org-attached only).
- Group detail: card with two tabs:
  - Tab 1: group information editing
  - Tab 2: member list for the entire group

Status
- [x] Org management landing lists manageable groups (tagged + org-attached)
- [x] Group detail tabs implemented (profile + members)
- [x] Group members page uses unified layout (search + list + modals + callouts)

## Configurability Requirements
- All groups strategy behavior must be configurable via existing org roster config array + filters.
- At minimum make configurable:
  - Managing roles list
  - Roster roles list and seat rules (Member/Observer)
  - Tag name/value used to select roster-management groups
  - End-date removal toggle and default end-date behavior
  - Seat availability/callout behavior linkage to existing strategy config

## Config Flags (Unified View Defaults)
- ui.member_view.use_unified: true (default)
- ui.member_list.use_unified: true (default)
- ui.member_list.show_edit_permissions: true (default; ignored for groups)
- ui.member_view.search_clear_requires_submit: false (default; groups override below)
- groups.ui.use_unified_member_view: true (default)
- groups.ui.use_unified_member_list: true (default)
- groups.ui.show_edit_permissions: false (default)
- groups.ui.search_clear_requires_submit: true (default)

Status
- [x] Groups config block added with manage roles, roster roles, tag, removal, seat-limited roles
- [x] Additional seats CTA reuse integrated in group member list
- [ ] Config filtering parity for all group settings (only base config array added; filter coverage not audited)
- [x] Unified view/list toggles are config-driven and default to enabled

## Interview Questions (still blocking)
1) How is organization identity stored in group role additional info (key name and format)?
2) Pagination/search/sort requirements for the group list and member list?

## Scan Notes (codebase + mdp-bruno)
- Existing groups strategy class already exists but is stubby and hardcodes role 'member'; needs full groups-based logic without breaking other strategies.
- Current strategy selection is config-driven: config['roster']['strategy'] with filter wicket/acc/orgman/roster_mode.
- Core member listing uses membership endpoints; groups strategy will need its own listing path or adapt membership list rendering.
- helper-groups.php provides MDP group endpoints:
  - GET /group_members?filter[person_uuid_eq]=...&include=group
  - GET /people/{person_uuid}/group_memberships?include=group,organization&filter[active_eq]=...
  - GET /groups?filter[organization_uuid_eq]=... (org groups)
  - GET /groups/{group_uuid}/people?filter[active_eq]=...&include=person,organization (group roster)
  - POST /group_members (add) and DELETE /group_members/{id} (remove)
- Group membership payload uses attributes.type as role slug and attributes.custom_data_field (currently null in helper).
- Existing add_member/remove_member flow uses MemberService + templates-partials/process/add-member.php and remove-member.php; these are membership-oriented.
- Group member directory template indicates custom_data_field key 'association' is used to map org identity (value contains name); needs confirmation for roster use.
- Legacy HTMX groups list uses wicket_get_org_groups and wicket_get_group_members; pagination is per groups meta.
- New GroupService added to library to centralize group list + membership access, filtering, and custom_data_field handling (configurable key/value).
- Template routing for new endpoints is handled via TemplateHelper map, using: group-members-list, process/add-group-member, process/remove-group-member, process/update-group.
- New group UI uses existing org-management pages with roster_mode === 'groups' gating; fallback to org flows when not in groups mode.
- New config block added: orgman_config['groups'] (tag name, roles, roster roles, seat limits, additional_info mapping, removal mode, UI edit fields).
- Debug logging added across groups strategy/services/process endpoints via wc_get_logger with source wicket-orgroster.
- Group members listing uses /groups/{group_uuid}/people with role filters and filters by org identifier derived from custom_data_field.
- Group list uses /people/{person_uuid}/group_memberships include=group,organization and filters by manage_roles + tag + org attachment.

Status
- [x] Groups strategy implemented (no longer stubby)
- [x] Strategy selection remains config-driven
- [x] Groups list + member list implemented via GroupService
- [x] Group endpoints wired via TemplateHelper
- [x] Unified members view renders search/list/pagination/modals/callouts for groups
- [x] Search submit-only behavior and clear swap implemented (no auto-search)
- [x] Loading indicator and card hiding during search implemented in unified list

## Notes
- Unified view is the default; legacy view/list remain available via config for per-site fallback.
