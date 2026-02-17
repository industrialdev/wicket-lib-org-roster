# Cascade Strategy: Seat Assignment

## Current Contract
- Cascade add flow assigns person to resolved organization membership seat.
- Membership assignment is part of add flow when person is not already assigned.

## Behavior
- Strategy resolves organization membership UUID then calls membership assignment helper.
- Assignment requires membership type ID from membership payload.
- On assignment failure, add mutation fails with explicit error.

## Additional Seats Integration
- Cascade mode uses shared additional-seats services and UI callouts.
- Seat purchase handling remains strategy-agnostic at integration layer.
