# General Specifications

`wicket-lib-org-roster` provides a unified interface for managing organization rosters within Wicket-integrated WordPress sites.

## 1. Functional Requirements

### 1.1 Organization Management
- List organizations associated with the current user.
- View organization profile and business information.
- Edit organization details (permissions permitting).

### 1.2 Roster Management
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
- Identify "Roster Management" groups via MDP tags.
- Manage group members for specific organizations.
- Seat limits enforced per group (e.g., exactly one "Member" role per org per group).

### 1.4 Additional Seats
- Integration with WooCommerce to allow organizations to buy more seats.
- Automatic update of Wicket membership seat limits upon order completion.
- Support for Gravity Forms-based purchase flows.

### 1.5 Document Management
- Upload and list documents for organizations.
- Support for configurable file types and size limits.

## 2. Configuration (`src/config/config.php`)

The library is highly configurable via the `wicket/acc/orgman/config` filter. Key sections include:

- `roster.strategy`: Selects the logic for member management (`direct`, `cascade`, `groups`).
- `permissions`: Fine-grained control over who can edit orgs, manage members, and buy seats.
- `member_addition`: Configuration for auto-assigned roles and relationship types.
- `groups`: Settings for group-based management (tags, managing roles, roster roles).
- `additional_seats`: SKU and Gravity Forms mapping for seat purchases.
- `ui`: Toggles for "Unified View", card fields, and layout modes.

## 3. WordPress Integration

### 3.1 Content Injection
The library automatically injects its UI into the following "My Account" page slugs:
- `organization-management`
- `organization-profile`
- `organization-members`
- `supplemental-members`

### 3.2 Hooks & Filters
- **Filter**: `wicket/acc/orgman/config` - Modify the entire library configuration.
- **Filter**: `wicket/acc/orgman/base_path` / `base_url` - Change asset and template locations.
- **Action**: `rest_api_init` - Registers API routes under `org-management/v1`.

## 4. REST API Endpoints

The library registers several endpoints under the `org-management/v1` namespace:
- `GET /configuration`: Returns active library configuration.
- `POST /business-info/update`: Updates organization business information.
- `POST /documents/upload`: Handles document uploads.
- `POST /subsidiary/search`: Searches for subsidiary organizations.
- (Internal process endpoints are managed via `TemplateHelper` and typically handle Datastar SSE requests).

## 5. Security Model
- **PermissionService**: Centralizes all access control.
- **Capability Checks**: Verifies if a user is an Administrator or has specific organization roles (`owner`, `org_editor`, `membership_manager`).
- **Confirmation**: Checks `confirmed_at` status for users before allowing certain actions.
