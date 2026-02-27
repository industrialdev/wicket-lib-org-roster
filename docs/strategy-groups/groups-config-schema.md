# Groups Strategy: Config Schema (Current Code)

## Principles
- Master switch is `roster.strategy`.
- `groups` config applies when `roster.strategy = groups`.
- Existing defaults for other strategies remain unchanged.

## Current Default Configuration (`src/Config/OrgManConfig.php`)

Global `ui` keys used by groups screens:
- `ui.organization_list.page_size`: `5` (used by non-groups organization-card pagination; groups mode uses group-membership list behavior)
- `ui.member_list.show_bulk_upload`: `false` by default (shared member CSV bulk upload is not rendered in groups mode)

`groups`:
- `tag_name`: `'Roster Management'`
- `tag_case_sensitive`: `false`
- `manage_roles`:
  - `'president'`
  - `'delegate'`
  - `'alternate_delegate'`
  - `'council_delegate'`
  - `'council_alternate_delegate'`
  - `'correspondent'`
- `roster_roles`:
  - `'member'`
  - `'observer'`
- `member_role`: `'member'`
- `observer_role`: `'observer'`
- `seat_limited_roles`: `['member']`
- `list.page_size`: `20`
  - Used as fetch page size for group-membership retrieval in groups landing resolution.
- `list.member_page_size`: `15`
- `additional_info`:
  - `key`: `'association'`
  - `value_field`: `'name'`
  - `fallback_to_org_uuid`: `true`
- `removal`:
  - `mode`: `'end_date'`
  - `end_date_format`: `'Y-m-d\\T00:00:00P'`
- `ui`:
  - `enable_group_profile_edit`: `true`
  - `use_unified_member_list`: `true`
  - `use_unified_member_view`: `true`
  - `show_edit_permissions`: `false`
  - `search_clear_requires_submit`: `true`
  - `editable_fields`: `['name', 'description']`

## Filter Contract
- Filter remains `wicket/acc/orgman/config`.
- Groups overrides are expected under `groups.*`.
- Keep global permission defaults untouched unless intentionally changing shared behavior.

## Backward-Compatible Rules
- Keep default `roster.strategy = 'direct'`.
- Enabling `groups` must be explicit.
- Missing `groups` block in filtered config falls back to defaults.
