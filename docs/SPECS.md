# Wicket Org Roster Library: Unified Specifications

`wicket-lib-org-roster` provides a unified interface for managing organization rosters within Wicket-integrated WordPress sites.

## 1. Functional Requirements

### 1.1 Organization Management
- List organizations associated with the current user.
- View organization profile and business information.
- Edit organization details (permissions permitting).

### 1.2 Base Roster Management
- **View Roster**: List members with pagination and search.
- **Member Card Semantics**:
  - Account confirmation indicators and unconfirmed helper copy are configurable through `ui.member_list.account_status.*`.
  - Displayed role labels can be constrained with `ui.member_list.display_roles_allowlist` and `ui.member_list.display_roles_exclude`.
  - Multiple relationship rows for the same person are merged into a single roster card.
  - Relationship include/exclude filtering is normalized before matching (for example, casing/spacing and common aliases like `affiliation` -> `affiliate`).
- **Add Member**:
  - Add via email/name.
  - Automatically assign base roles and relationship types.
  - Check seat limits before adding.
- **Remove Member**:
  - Remove connection/role or end-date membership.
  - Supports "end-date" removal mode for soft deletes.
- **Edit Permissions**: Modify roles and relationship types for existing members.
- **Bulk Upload Members (CSV)**:
  - Config-gated by `ui.member_list.show_bulk_upload` (default `false`).
  - Available in non-groups member views for users who can add members.
  - Additive only: rows are added when valid; duplicates/invalid rows are skipped with summary reporting.

### 1.3 Group Roster Strategy
- Identify "Roster Management" groups via MDP tags (`groups.tag_name`, case-sensitivity configurable by `groups.tag_case_sensitive`).
- On `organization-management` in groups mode:
  - show active tagged groups the user belongs to,
  - if exactly one group is found, redirect directly to that group,
  - if multiple groups are found, show the group list.
- Group list visibility is independent from organization membership/org roles; action links are role-gated.
- Manage group members for specific organizations. 
  - Organization membership is determined by matching the group role additional info (`custom_data_field`) between the managing user's role and the roster member's role. 
  - Users cannot view or manage roster entries for other organizations within the same group.
- Seat limits enforced per group:
  - Each organization may have exactly one "Member" per group. (The one-Member rule is enforced strictly per group, not globally across groups).
  - Organizations may have unlimited "Observers" per group.
  - Seat availability follows existing direct/cascading strategy rules and callouts, including the additional seats purchase CTA.
- **Management Capabilities**:
  - Authorized users can view their org's roster, add Members/Observers, and remove them.
  - When adding a user, the group role additional info is automatically populated to match the managing user's org.
  - Replacing the single Member role is done by removing the existing Member and adding a new one.
  - Users cannot edit the details of existing roster entries (only add or remove). Users may add themselves as a Member or Observer if applicable.

### 1.4 Additional Seats
- Integration with WooCommerce to allow organizations to buy more seats.
- Automatic update of Wicket membership seat limits upon order completion.
- Support for Gravity Forms-based purchase flows.

### 1.5 Document Management
- Upload and list documents for organizations.
- Support for configurable file types and size limits.

### 1.6 Membership Cycle Strategy
- Supports `membership_cycle` strategy key for cycle-scoped roster management.
- Mutating cycle-scoped actions require explicit `membership_uuid` context.
- Cycle-scoped add/remove flows validate membership-to-organization scope.
- Cycle-scoped remove path protects owner removal and end-dates targeted person memberships.
- Unified list/view/process flows propagate `membership_uuid` to keep refresh/search/pagination cycle-safe.

### 1.7 Organization Summary Card
- Organization summary rendering includes Membership Tier, Membership Owner, Renewal Date, and seats.
- Owner name resolution supports API payload variants (`data.attributes`, direct `attributes`, and included person entities).
- Renewal date resolution checks common membership date fields (`ends_at`, `end_at`, `expires_at`, `renewal_date`, `next_renewal_at`) before formatting.

## 2. Configuration (`src/config/config.php`)

The library is highly configurable via the `wicket/acc/orgman/config` filter. Key sections include:

- `roster.strategy`: Selects the logic for member management (`direct`, `cascade`, `groups`, `membership_cycle`).
- `permissions`: Fine-grained control over who can edit orgs, manage members, and buy seats.
- `member_addition`: Configuration for auto-assigned roles and relationship types.
- `groups`: Settings for group-based management (tags, managing roles, roster roles, seat limits, additional_info mapping, removal mode, UI edit fields).
- `membership_cycle`: Strategy-specific permissions and member-management guards.
- `additional_seats`: SKU and Gravity Forms mapping for seat purchases.
- `ui`: Toggles for "Unified View", card fields, and layout modes.
  - `ui.organization_list.page_size`: organization card page size for `organization-management` (default `5`).
    - Applies to non-groups organization cards; groups mode uses group-membership list behavior.
  - **Unified View Config Flags (Groups)**:
    - `ui.member_view.use_unified`: true (default)
    - `ui.member_list.use_unified`: true (default)
    - `ui.member_list.show_bulk_upload`: false (default)
    - `groups.ui.use_unified_member_view`: true (default)
    - `groups.ui.use_unified_member_list`: true (default)
    - `groups.ui.show_edit_permissions`: false (default)
    - `groups.ui.search_clear_requires_submit`: true (default)

## 3. WordPress Integration

### 3.1 Content Injection
The library automatically injects its UI into the following "My Account" page slugs:
- `organization-management` (Lists organizations in non-groups modes; in groups strategy this renders the active tagged group list, with single-group auto-redirect).
- `organization-profile` (Group detail card tab 1: group information editing).
- `organization-members` (Group detail card tab 2: member list for the entire group, defaulting to the unified view).
- `supplemental-members`

*Note: The unified view supports debounced search input plus explicit Search/Clear actions, with loading indicators and card hiding. End-dated roles are excluded from the member list UI.*

### 3.2 Hooks & Filters
- **Filter**: `wicket/acc/orgman/config` - Modify the entire library configuration.
- **Filter**: `wicket/acc/orgman/base_path` / `base_url` - Change asset and template locations.
- **Action**: `rest_api_init` - Registers API routes under `org-management/v1`.

## 4. REST API & Internal Endpoints

### 4.1 REST API Routes
The library registers several endpoints under the `org-management/v1` namespace:
- `GET /configuration`: Returns active library configuration.
- `POST /business-info/update`: Updates organization business information.
- `POST /documents/upload`: Handles document uploads.
- `POST /subsidiary/search`: Searches for subsidiary organizations.

### 4.2 Internal & MDP Endpoints (Groups)
- Internal process endpoints are managed via `TemplateHelper` and typically handle Datastar SSE requests. Group template routing uses: `group-members-list`, `process/add-group-member`, `process/remove-group-member`, `process/update-group`.
- Group-specific MDP endpoints used by `GroupService` (`helper-groups.php`):
  - `GET /group_members?filter[person_uuid_eq]=...&include=group`
  - `GET /people/{person_uuid}/group_memberships?include=group,organization&filter[active_eq]=...`
  - `GET /groups?filter[organization_uuid_eq]=...`
  - `GET /groups/{group_uuid}/people?filter[active_eq]=...&include=person,organization`
  - `POST /group_members` (add) and `DELETE /group_members/{id}` (remove)

## 5. Security Model

- **PermissionService**: Centralizes all access control.
- **Capability Checks**: Verifies if a user is an Administrator or has specific organization roles (`owner`, `org_editor`, `membership_manager`).
- **Confirmation**: Checks `confirmed_at` status for users before allowing certain actions.
- **Group Access Security**:
  - By default, only users with active group role in `groups.manage_roles` (`president`, `delegate`, `alternate_delegate`, `council_delegate`, `council_alternate_delegate`, `correspondent`) may manage group roster actions.
  - Users cannot remove or modify these managing roles, nor can they explicitly remove themselves from these managing roles.

## 6. Current Implementation Status (As of February 23, 2026)

**Completed (Groups):**
- Groups strategy wiring, `GroupService`, templates, and process endpoints (no longer stubby).
- Organization-management groups landing now lists active tagged group memberships and auto-redirects only when one group exists.
- Group list now includes non-manage roles for visibility, while management links are role-gated (`can_manage_group` / `groups.manage_roles`).
- Group cards resolve organization display names via included org data with `wicket_get_organization` fallback.
- Role-based access checks, tag filtering, and roster-role validation (member/observer seat limits in add flow).
- Removal supports end-date and delete modes (configurable). End-dated roles are treated as removed (`active=true` queries).
- Group org-scope matching now normalizes identifier/name/uuid tokens for member listing and removal lookups.
- Groups config block added.
- Unified members view (search + list + pagination + modals + seats callout) is the default. Legacy list preserved behind config flags.
- Debug logging added across groups strategy via `wc_get_logger` (source: `wicket-orgroster`).

**Open / Needs Confirmation (Groups):**
- Org identifier key/format in `custom_data_field` must be confirmed and aligned with MDP data.
- Role identifiers are config-driven, not yet derived dynamically from MDP responses.
- Seat-limit enforcement for the member role currently only checks the first 50 members.
- Group-list sorting beyond current name sort is not formally specified.
- Explicit self-removal prevention for managing roles is not separately enforced (covered by general role block).
- Config filtering parity for all group settings (only base config array added).

**Completed (Membership Cycle):**
- Strategy key `membership_cycle` is registered in `MemberService`.
- Additive `membership_cycle` config block exists in `src/config/config.php`.
- Direct strategy accepts explicit membership UUID override with org-scope validation.
- Membership-cycle strategy class exists with explicit membership UUID guards.
- Process add/remove handlers enforce membership UUID requirements in `membership_cycle` mode.
- Membership-cycle remove behavior end-dates person membership and protects owner removal.
- PermissionHelper applies strategy-local overrides for add/remove/purchase roles when in `membership_cycle` mode.
- Unified/legacy member list and refresh/search flows propagate `membership_uuid`.
- Unit tests cover strategy wiring and critical membership-cycle validation paths.

**Open / Needs Confirmation (Membership Cycle):**
- Cycle tabs and multi-roster cycle resolver UI are not fully implemented.
- ESCRS-specific bulk-upload whitelist enforcement (membership label validation) is not fully implemented yet.
- Full cycle-status mapping (`active/delayed/upcoming`) is not yet implemented in UI rendering.
