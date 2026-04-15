---
title: "Current Library Spec"
audience: [developer, implementer]
source_files: ["src/OrgMan.php", "src/Services/Strategies/"]
---

# Current Library Spec

This file describes what the library does today.

## Supported Strategy Keys

- `direct`
- `cascade`
- `groups`
- `membership_cycle`

## Supported Account Screens

- organization list and detail flows
- organization member management
- group member management in groups mode
- supplemental-members purchase flow for additional seats
- organization-members-bulk page when bulk upload UI is enabled

## Implemented Runtime Capabilities

- singleton bootstrap through `OrgManagement\OrgMan`
- strategy-based member add and remove flows
- shared CSV bulk-upload process handler
- group-member add and remove flows
- additional-seats checkout integration
- organization, member, membership, group, and permission services
- config-driven unified and legacy member views
- hypermedia partial endpoint via `TemplateHelper`

## Current Runtime Gaps

- no bundled automated tests in this package
- no cycle-tab resolver UI for `membership_cycle`
- no packaged documentation guarantee that additional-seats UI propagation is cycle-specific across every membership-cycle surface

## Bulk Upload

Shared bulk upload is implemented through `templates-partials/process/bulk-upload-members.php`.

Current characteristics:

- disabled by default
- additive only
- strategy-aware through `MemberService`
- available to non-groups and membership-cycle flows when the UI flag is enabled

## Additional Seats

Current additional-seats flow includes:

- Gravity Forms capture
- WooCommerce cart and checkout handoff
- order processing hooks in `OrgMan`
- membership ID and organization UUID persistence in session, user meta, order item meta, and order meta

Current documentation limit:

- the library stores `membership_id` and uses it during seat processing
- the package does not currently ship a dedicated cycle-specific UI layer proving every membership-cycle surface passes the intended membership context
