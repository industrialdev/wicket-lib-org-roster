# Direct Strategy: Seat Assignment

## Current Contract
- Direct add flow assigns person to an organization membership seat.
- Membership target is either explicit context UUID or org-resolved UUID.

## Behavior
- Seat assignment uses existing helper integration and membership service lookup.
- On helper/API error, strategy verifies whether assignment already exists before failing.
- Additional seats purchase UX/integration is shared and strategy-agnostic at service layer.

## Risk Note
- In organizations with concurrent memberships, implicit resolver fallback may target an unintended cycle.
- Mitigation: pass explicit `membership_uuid` in direct-mode contexts when precision is required.
