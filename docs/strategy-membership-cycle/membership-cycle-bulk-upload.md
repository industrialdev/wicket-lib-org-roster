# Membership Cycle Strategy: Bulk Upload

## Current Implementation (Library)
- CSV bulk upload is available through shared organization member views when:
  - `roster.strategy = membership_cycle`
  - `ui.member_list.show_bulk_upload = true` (default `false`)
  - current user can add members under membership-cycle permissions
- Endpoint: `templates-partials/process/bulk-upload-members.php`
- The process path uses cycle-aware add behavior by passing `membership_uuid` into `MemberService->add_member()`.

## Current CSV Contract
- Required columns:
  - `First Name`
  - `Last Name`
  - `Email Address`
- Optional column:
  - `Roles`
- Additive-only import: no deletions.
- Duplicate active members in the target membership are skipped.
- Response provides summary counts (processed/added/skipped/failed).

## Remaining Membership-Cycle Specific Work
- Add strict membership-label whitelist enforcement for ESCRS-specific values.
- Add row-level whitelist result classification (`invalid_membership`, etc.).
- Add dedicated tests for whitelist behavior and cycle seat-limit handling.
