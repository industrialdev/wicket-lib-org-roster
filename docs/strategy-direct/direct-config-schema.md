# Direct Strategy: Config Schema (Current Code)

## Principles
- Master switch is `roster.strategy`.
- Direct strategy uses shared/global config keys; there is no dedicated `direct` namespace block.
- Existing defaults for other strategies remain unchanged.

## Required Strategy Switch
- `roster.strategy`: `'direct'` (default)

## Direct-Relevant Config Keys (`src/config/config.php`)
- `permissions`:
  - `add_members`
  - `remove_members`
  - `manage_members`
  - `prevent_owner_removal`
  - `prevent_owner_assignment`
- `member_addition`:
  - `base_member_role`
  - `auto_assign_roles`
  - `auto_opt_in_communications`
- `relationships`:
  - `member_addition_type`
  - `default_type`
- `ui`:
  - shared member list/view and member card field flags.
  - `ui.member_list.show_bulk_upload` (default `false`; enable to show CSV bulk upload panel)

## Context Contract (Runtime)
- Optional membership override in add context:
  - `membership_uuid`
  - `membership_id`
- Required for direct strategy remove call:
  - `person_membership_id`

## Filter Contract
- Filter remains `wicket/acc/orgman/config`.
- Direct behavior changes are done by overriding shared keys listed above.

## Backward-Compatible Rules
- Keep `roster.strategy = 'direct'` default unless explicitly changing deployment behavior.
- Do not remove or rename existing shared config keys.
