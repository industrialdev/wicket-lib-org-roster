# Wicket Org Roster Library: Unified Specifications

`wicket-lib-org-roster` provides a unified interface for managing organization rosters within Wicket-integrated WordPress sites.

## 1. Functional Requirements

### 1.1 Organization Management
- List organizations associated with the current user.
- View organization profile and business information.
- Edit organization details (permissions permitting).

### 1.2 Base Roster Management
- **View Roster**: List members with pagination and search.
- **Add Member**:
  - Add via email/name.
  - Automatically assign base roles and relationship types.
  - Check seat limits before adding.
- **Remove Member**:
  - Remove connection/role or end-date membership.
  - Supports "end-date" removal mode for soft deletes.
- **Edit Permissions**: Modify roles and relationship types for existing members.

### 1.3 Group Roster Strategy
- Identify "Roster Management" groups via MDP tags (case-sensitive as shown in MDP data).
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

## 2. Configuration (`src/config/config.php`)

The library is highly configurable via the `wicket/acc/orgman/config` filter. Key sections include:

- `roster.strategy`: Selects the logic for member management (`direct`, `cascade`, `groups`, `membership_cycle`).
- `permissions`: Fine-grained control over who can edit orgs, manage members, and buy seats.
- `member_addition`: Configuration for auto-assigned roles and relationship types.
- `groups`: Settings for group-based management (tags, managing roles, roster roles, seat limits, additional_info mapping, removal mode, UI edit fields).
- `additional_seats`: SKU and Gravity Forms mapping for seat purchases.
- `ui`: Toggles for "Unified View", card fields, and layout modes.
  - **Unified View Config Flags (Groups)**:
    - `ui.member_view.use_unified`: true (default)
    - `ui.member_list.use_unified`: true (default)
    - `groups.ui.use_unified_member_view`: true (default)
    - `groups.ui.use_unified_member_list`: true (default)
    - `groups.ui.show_edit_permissions`: false (default)
    - `groups.ui.search_clear_requires_submit`: true (default)

## 3. WordPress Integration

### 3.1 Content Injection
The library automatically injects its UI into the following "My Account" page slugs:
- `organization-management` (Lists eligible groups for the groups strategy: user-accessible + org-attached only).
- `organization-profile` (Group detail card tab 1: group information editing).
- `organization-members` (Group detail card tab 2: member list for the entire group, defaulting to the unified view).
- `supplemental-members`

*Note: The unified view implements a submit-only search UX with loading indicators and card hiding. End-dated roles are excluded from the member list UI.*

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
  - Only users assigned directly by IAA staff in the MDP as a *President*, *Council Delegate*, *Council Alternate Delegate*, or *Correspondent* may manage their organization's roster. Losing this role instantly revokes access.
  - Users cannot remove or modify these managing roles, nor can they explicitly remove themselves from these managing roles.

## 6. Current Implementation Status (Groups Strategy - 2026-02-02)

**Completed:**
- Groups strategy wiring, `GroupService`, templates, and process endpoints (no longer stubby).
- Group list and member list UIs exist with pagination and search.
- Role-based access checks, tag filtering, and roster-role validation (member/observer seat limits in add flow).
- Removal supports end-date and delete modes (configurable). End-dated roles are treated as removed (`active=true` queries).
- Groups config block added.
- Unified members view (search + list + pagination + modals + seats callout) is the default. Legacy list preserved behind config flags.
- Debug logging added across groups strategy via `wc_get_logger` (source: `wicket-orgroster`).

**Open / Needs Confirmation:**
- Org identifier key/format in `custom_data_field` must be confirmed and aligned with MDP data.
- Role identifiers are config-driven, not yet derived dynamically from MDP responses.
- Seat-limit enforcement for the member role currently only checks the first 50 members.
- Group list sorting requirements not defined/implemented.
- Explicit self-removal prevention for managing roles is not separately enforced (covered by general role block).
- Config filtering parity for all group settings (only base config array added).
