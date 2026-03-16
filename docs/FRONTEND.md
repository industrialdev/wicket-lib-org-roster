# Frontend

## Runtime Surfaces

The library renders into WordPress account pages with these slugs:

- `organization-management`
- `organization-profile`
- `organization-members`
- `organization-members-bulk`
- `supplemental-members`

## Templates

- page templates live in `templates/`
- reusable partials live in `templates-partials/`
- mutating process handlers live in `templates-partials/process/`

Important process handlers in current use:

- `process/add-member.php`
- `process/remove-member.php`
- `process/bulk-upload-members.php`
- `process/add-group-member.php`
- `process/remove-group-member.php`
- `process/update-group.php`
- `process/update-permissions.php`

## Hypermedia Endpoint

`TemplateHelper` exposes partials through:

- `?action=hypermedia&template=...`

It also:

- registers query vars
- normalizes `org_id` to `org_uuid`
- loads partial templates from the library safely

## Assets

- main stylesheet: `public/css/modern-orgman-static.css`
- helper scripts:
  - `public/js/datastar-error-handler.js`
  - `public/js/orgman-notifications.js`
  - `public/js/orgman-content-behaviors.js`

## Config Flags That Affect Rendering

- `ui.organization_list.*`
- `ui.member_list.use_unified`
- `ui.member_list.show_edit_permissions`
- `ui.member_list.show_remove_button`
- `ui.member_list.show_bulk_upload`
- `ui.member_list.account_status.*`
- `ui.member_list.remove_policy_callout.*`
- `ui.member_view.use_unified`
- `ui.member_view.search_clear_requires_submit`
- `ui.member_card_fields.*`
- `groups.ui.*`

## Current Limits

- membership-cycle mode reuses shared member pages; it does not ship a cycle-tab UI or cycle resolver
- bulk upload UI is shared and only appears when `ui.member_list.show_bulk_upload = true`
