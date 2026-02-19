# Direct Strategy: Bulk Upload

## Current State
- CSV bulk upload is available through shared member views when:
  - `roster.strategy = direct`
  - `ui.member_list.show_bulk_upload = true`
  - current user can add members
- Endpoint: `templates-partials/process/bulk-upload-members.php`
- Direct strategy processing reuses `MemberService->add_member()` with direct membership resolution behavior.

## Current Behavior
- CSV columns expected: first name, last name, email, optional roles.
- Additive-only workflow: no removals.
- Duplicate active members in the target membership are skipped.
- Summary reporting includes processed/added/skipped/failed counts.
