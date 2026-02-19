# Cascade Strategy: Config Schema (Current Code)

## Principles
- Master switch is `roster.strategy`.
- Cascade strategy uses shared/global config keys; there is no dedicated `cascade` namespace block.
- Existing defaults for other strategies remain unchanged.

## Strategy Switch
- `roster.strategy`: `'cascade'`

## Cascade-Relevant Config Keys (`src/config/config.php`)
- `permissions`:
  - `add_members`
  - `remove_members`
  - `manage_members`
  - `prevent_owner_assignment`
  - `relationship_based_permissions`
  - `relationship_roles_map`
- `member_addition`:
  - `base_member_role`
  - `auto_assign_roles`
- `relationships`:
  - `default_type`
  - `member_addition_type`
- `ui`:
  - `ui.member_list.show_bulk_upload` (default `false`; enable to show CSV bulk upload panel)

## Runtime Context Contract
- Add context supports:
  - `relationship_type`
  - `relationship_description`
  - `roles`
- Remove context requires:
  - `person_membership_id`

## Filter Contract
- Filter remains `wicket/acc/orgman/config`.
- Cascade behavior changes should be done by overriding shared keys above.

## Backward-Compatible Rules
- Keep cascade behavior compatible with legacy side-effects.
- Avoid introducing cascade-only keys unless strictly necessary.
