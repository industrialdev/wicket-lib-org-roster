# Cascade Strategy: Bulk Upload

## Current State
- CSV bulk upload is available through shared member views when:
  - `roster.strategy = cascade`
  - `ui.member_list.show_bulk_upload = true`
  - current user can add members
- Endpoint: `templates-partials/process/bulk-upload-members.php`
- Cascade behavior is preserved by routing each row through `MemberService->add_member()`.

## Current Behavior
- CSV columns expected: first name, last name, email, optional roles.
- Additive-only workflow: no removals.
- Duplicate active members in the target membership are skipped.
- Summary reporting includes processed/added/skipped/failed counts.
