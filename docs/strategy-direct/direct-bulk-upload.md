# Direct Strategy: Bulk Upload

## Current State
- Dedicated direct-strategy bulk upload flow is not implemented in current code.
- Direct strategy member mutations are interactive add/remove operations.

## If Added Later
- Keep implementation strategy-safe and org-scoped.
- Reuse direct membership resolution rules (explicit membership UUID preferred).
- Validate duplicates within target membership scope.
- Report row-level outcomes (`added`, `duplicate`, `invalid`, `error`).
