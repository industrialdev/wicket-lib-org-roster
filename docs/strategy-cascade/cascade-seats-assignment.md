# Cascade Strategy: Seat Assignment

## Current Contract
- Cascade add flow does not directly assign person-membership seats.
- Membership assignment is delegated to downstream/system cascade automation.
- Strategy enforces seat-capacity checks before creating a new relationship.

## Behavior
- Strategy resolves organization membership UUID and reads membership payload for seat counters.
- Capacity check uses `active_assignments_count` against effective `max_assignments`.
- When no seats are available, add mutation fails with explicit `seat_limit_reached` error.

## Additional Seats Integration
- Cascade mode uses shared additional-seats services and UI callouts.
- Seat purchase handling remains strategy-agnostic at integration layer.
