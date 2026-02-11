# Membership Cycle Strategy: Config Schema (Additive + Backward-Compatible)

## Principles
- All new settings live under `membership_cycle`.
- Existing defaults in `roster`, `permissions`, `ui`, `additional_seats`, and `member_addition_form` remain unchanged.
- Master switch is `roster.strategy`.
- `membership_cycle` settings are only applied when `roster.strategy = membership_cycle`.
- Missing keys must always fall back to existing library behavior.

## Proposed Config Block
Add this block to `get_config()` as an additive key:

`membership_cycle`:
- `strategy_key`: `'membership_cycle'`
- `memberships`:
  - `allowed_labels`:
    - `'ESCRS Membership National Society Full'`
    - `'ESCRS Membership National Society Trainee'`
  - `label_to_tier`:
    - `'ESCRS Membership National Society Full' => 'full'`
    - `'ESCRS Membership National Society Trainee' => 'trainee'`
  - `allowed_durations`: `[1, 3]`
  - `max_visible_rosters_soft_limit`: `16`
- `cycles`:
  - `tabs`:
    - `'active'`
    - `'following'`
  - `status_map`:
    - `active`: `['active']`
    - `delayed`: `['delayed']`
    - `upcoming`: `['upcoming', 'renewed']`
  - `show_renewal_overlap`: `true`
  - `show_legacy_migration_overlap`: `true`
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
- `bulk_upload`:
  - `enabled`: `true`
  - `allowed_columns`:
    - `'first_name'`
    - `'last_name'`
    - `'email'`
    - `'membership'`
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
  - `table_fields`:
    - `'name'`
    - `'nominal'`
    - `'email'`
    - `'membership'`
    - `'membership_status'`
  - `search_fields`: `['name', 'email']`
  - `page_size`: `15`
  - `search_clear_requires_submit`: `true`
- `logging`:
  - `enabled`: `true`
  - `include_membership_uuid`: `true`
  - `source`: `'wicket-orgman'`

## Filter Contract
- Continue to expose all changes through existing filter: `wicket/acc/orgman/config`.
- Third-party overrides remain additive:
  - Overriding only `membership_cycle.permissions.add_roles` must not alter global `permissions.add_members`.
  - Overriding `membership_cycle.ui.page_size` must not alter `ui.member_list` defaults for other strategies.

## Non-Breaking Merge Rules
- Keep `roster.strategy` default as `'direct'`.
- Extend documented/validated strategy values to include `'membership_cycle'` without changing default behavior.
- Do not change any existing key names or existing default values.
- Do not add secondary activation flags; activation is via `roster.strategy = membership_cycle` only.
- When `membership_cycle` key is absent, behavior is identical to current production behavior.

## Minimal Example Override
Recommended pattern for implementers:
- Read base config from `\OrgManagement\Config\get_config()`.
- Merge only `membership_cycle` nested keys in filter callback.
- Avoid replacing top-level arrays (`permissions`, `ui`, `additional_seats`) unless intentionally changing global behavior.
