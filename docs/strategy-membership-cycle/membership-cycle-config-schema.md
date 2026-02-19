# Membership Cycle Strategy: Config Schema (Current Code)

## Principles
- Master switch is `roster.strategy`.
- `membership_cycle` config is additive and only applies when `roster.strategy = membership_cycle`.
- Existing defaults remain unchanged for `direct`, `cascade`, and `groups`.

## Current Default Config Block (`src/config/config.php`)

`membership_cycle`:
- `strategy_key`: `'membership_cycle'`
- `permissions`:
  - `add_roles`: `['membership_manager']`
  - `remove_roles`: `['membership_manager']`
  - `purchase_seats_roles`: `['membership_owner', 'membership_manager', 'org_editor']`
  - `prevent_owner_removal`: `true`
- `member_management`:
  - `require_explicit_membership_uuid`: `true`

Shared UI flag relevant to cycle bulk import:
- `ui.member_list.show_bulk_upload`: `false` (must be enabled to expose CSV bulk upload UI)

## Filter Contract
- Filter remains `wicket/acc/orgman/config`.
- Strategy-local overrides are expected under `membership_cycle.*`.
- Keep global `permissions` defaults intact unless intentionally changed.

## Backward-Compatible Rules
- Keep default `roster.strategy = 'direct'`.
- Add `membership_cycle` as an allowed strategy value without changing defaults.
- Avoid adding secondary toggles; activation is strategy-selection only.
- If `membership_cycle` is absent from a filtered config, existing behavior remains unchanged.
