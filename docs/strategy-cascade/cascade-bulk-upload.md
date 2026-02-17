# Cascade Strategy: Bulk Upload

## Current State
- Dedicated cascade-strategy bulk-upload flow is not implemented in current strategy code.
- Member mutations in cascade mode are handled through interactive add/remove flows.

## If Added Later
- Keep implementation strategy-scoped and backward-safe.
- Reuse cascade membership resolution and seat assignment behavior.
- Preserve relationship-mapping rules for role assignment.
- Emit row-level outcomes (`added`, `duplicate`, `invalid`, `error`).
