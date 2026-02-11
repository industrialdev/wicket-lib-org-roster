# Membership Cycle Strategy: Config Schema (Current Code)

## Principles
- Master switch is `roster.strategy`.
- `membership_cycle` config is additive and only applies when `roster.strategy = membership_cycle`.
- Existing defaults remain unchanged for `direct`, `cascade`, and `groups`.

## Current Default Config Block (`src/config/config.php`)

`membership_cycle`:
- `strategy_key`: `'membership_cycle'`
- `permissions`:
  - `view_roles`: `['membership_owner', 'membership_manager']`
  - `add_roles`: `['membership_manager']`
  - `remove_roles`: `['membership_manager']`
  - `bulk_upload_roles`: `['membership_manager']`
  - `purchase_seats_roles`: `['membership_owner', 'membership_manager', 'org_editor']`
  - `prevent_owner_removal`: `true`
- `member_management`:
  - `require_explicit_membership_uuid`: `true`
  - `duplicate_scope`: `'membership_uuid_active_only'`
  - `removal_mode`: `'end_date'`
  - `removal_end_date_format`: `'Y-m-d\\T00:00:00P'`
- `bulk_upload` (config exists; full bulk upload flow is still pending):
  - `enabled`: `true`
  - `allowed_columns`: `['first_name', 'last_name', 'email', 'membership']`
  - `membership_column`: `'membership'`
  - `membership_value_mode`: `'label_strict'`
  - `on_invalid_membership`: `'skip_row'`
  - `on_duplicate`: `'skip_row'`
  - `allow_delete`: `false`
  - `max_rows_per_file`: `2000`
  - `dry_run_default`: `false`
- `seats`:
  - `enforce_per_membership_uuid`: `true`
  - `show_capacity_alert`: `true`
  - `alert_message`: `'All seats have been assigned. Please purchase additional seats to add more members.'`
  - `purchase_button_label`: `'Purchase Additional Seats'`
  - `require_membership_uuid_in_checkout_metadata`: `true`
- `ui`:
  - `use_unified_member_view`: `true`
  - `use_unified_member_list`: `true`
  - `show_cycle_tabs`: `true`
  - `default_tab`: `'active'`
  - `show_membership_metadata`: `true`
  - `table_fields`: `['name', 'nominal', 'email', 'membership', 'membership_status']`
  - `search_fields`: `['name', 'email']`
  - `page_size`: `15`
  - `search_clear_requires_submit`: `true`

## Filter Contract
- Filter remains `wicket/acc/orgman/config`.
- Strategy-local overrides are expected under `membership_cycle.*`.
- Keep global `permissions` defaults intact unless intentionally changed.

## Backward-Compatible Rules
- Keep default `roster.strategy = 'direct'`.
- Add `membership_cycle` as an allowed strategy value without changing defaults.
- Avoid adding secondary toggles; activation is strategy-selection only.
- If `membership_cycle` is absent from a filtered config, existing behavior remains unchanged.
