# Groups Strategy: Seat Assignment

## Current Contract
- Seat-limited roles are configured by `groups.seat_limited_roles`.
- Default seat-limited role set: `['member']`.
- Contract is one seat-limited role assignment per organization per group.

## Behavior
- Before adding a seat-limited role, strategy checks existing group members in the same group + org association.
- If an occupied seat is found, add fails with `seat_unavailable`.
- Non-seat-limited roles are not constrained by this rule.

## Additional Seats Integration
- Groups strategy reuses existing additional-seats experience and callouts.
- Seat purchases remain tied to existing membership/additional-seats infrastructure.

## Known Constraint
- Current seat check inspects the fetched member window in add flow (size 50).
- If group population exceeds this window, stricter full-scan behavior may be required.
