# Groups Strategy: Bulk Upload

## Current State
- Dedicated groups bulk-upload flow is not implemented in current strategy code.
- Shared member CSV bulk upload is intentionally hidden in groups mode (`mode !== groups` guard).
- Group member management remains explicit add/remove operations.

## If Added Later
- Keep it strategy-scoped (`groups` only).
- Enforce same role/tag/org-association constraints as interactive add/remove.
- Validate role whitelist from `groups.roster_roles`.
- Enforce seat-limited-role constraints before write.
- Report row-level outcomes (`added`, `duplicate`, `invalid_role`, `seat_full`, `denied`, `error`).
