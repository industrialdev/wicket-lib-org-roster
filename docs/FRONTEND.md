# Frontend Development

This document explains the reactive frontend patterns used in the `wicket-lib-org-roster` library.

## 1. Reactive Pattern (Datastar)

The library uses [Datastar](https://data-star.dev/) to provide a Single Page Application (SPA) feel within WordPress without the complexity of a heavy framework like React or Vue.

### 1.1 Signals
State is stored in "signals" defined in the HTML.
- **Initialization**: `data-signals='{ "show_modal": false, "search": "" }'`
- **Binding**: Use `data-bind` to sync inputs with signals.
- **Usage**: Use signals in expressions, e.g., `data-show="signals.show_modal"`.

### 1.2 Server-Sent Events (SSE)
Instead of returning JSON, our REST API returns SSE streams that patch the DOM directly.
- **Trigger**: `data-on-click="$$post('/wp-json/org-management/v1/...')"`
- **Response**: The server uses `DatastarSSE` to send HTML fragments.
- **Merging**: Fragments are merged into the DOM based on the `selector` and `mode` (Inner, Outer, Append, etc.).

### 1.3 Fragments
Fragments are small pieces of PHP templates. When an action occurs (e.g., adding a member), the server renders the "Success" fragment and the "Updated List" fragment, sending them both in a single SSE stream.

## 2. Styling (Scoped Vanilla CSS + BEM)

### 2.1 Prefixing
To avoid conflicts with WordPress themes and other plugins, all scoped utility-style classes **MUST** use the `wt_` prefix and component classes should follow BEM naming.
- **Example (scoped utilities)**: `wt_bg-blue-600 wt_text-white wt_p-4`
- **Example (BEM)**: `members-search__input members-search__actions`

### 2.2 Source of Truth
- **Primary stylesheet**: `public/css/modern-orgman-static.css`
- **Build tooling**: None required at runtime (no Tailwind/NPM pipeline)

## 3. The "Unified View"
The Unified View is a search-centric interface for managing rosters.
- **Loading States**: Use the `searching` signal to show/hide loading indicators during API calls.
- **Search Logic**: Search supports debounced input (minimum 3 chars or clear-to-empty) and explicit Search/Clear buttons in unified templates.
- **Cycle Scope Propagation**: In `membership_cycle` mode, `membership_uuid` is propagated through list/search/pagination/add/remove refresh requests to keep operations cycle-scoped.
- **Pagination Controls**: `Previous` is hidden on page 1; `Next` is hidden on the last page; both are hidden when only one page exists.
- **Shared Role Visibility Toggle**: Member-card role display is controlled globally via `ui.member_card_fields.roles.enabled` across strategies.
- **Account Status Copy**: Unconfirmed/confirmed tooltips and inline unconfirmed labels are configurable via `ui.member_list.account_status.*`.
- **Role Label Filtering**: Member-card displayed roles can be filtered with `ui.member_list.display_roles_allowlist` and `ui.member_list.display_roles_exclude`.
- **Duplicate Member Rows**: Unified/legacy member lists render one card per person even when multiple eligible relationships exist for the same organization.

## 3.1 Organization Management List
- Organization cards on `organization-management` are paginated with `ui.organization_list.page_size` (default `5`).
- Current organization page is read from `org_page` query arg; values are sanitized and clamped to valid page range.
- In `groups` strategy, this route switches to a groups list UX:
  - heading: `Manage Groups`
  - summary: `Groups Found: N`
  - rows come from active, tagged group memberships for the current user.
  - if there is exactly one row, the user is redirected to that group's `organization-members` URL.
- Card rendering is strategy-specific:
  - `groups`: row shows group name, organization label, and current user role; action links are shown only for manageable roles.
  - `direct` / `cascade` / `membership_cycle`: card shows membership + roles and group labels where available.

## 3.2 Bulk Upload UI (CSV)
- The member bulk-upload panel is controlled by `ui.member_list.show_bulk_upload` (default `false`).
- The panel renders in organization member views for non-groups modes, in the same CTA region as member-management actions.
- Datastar signals used by this flow:
  - `bulkUploadSubmitting`
  - `membersLoading`
- Endpoint used by the form:
  - `templates-partials/process/bulk-upload-members.php`

## 4. Modals and Overlays
Modals are managed via signals and the `notifications-container.php`.
- Action handlers patch the modal content into the container and set the `show_modal` signal to `true`.
- Closing the modal simply sets the signal back to `false`.
