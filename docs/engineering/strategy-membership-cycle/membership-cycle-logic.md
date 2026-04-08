# Membership Cycle Strategy: Logic

Membership-cycle mode exists to prevent cross-cycle mutations when an organization has more than one relevant membership.

## Current Logic

- require explicit target membership UUID
- validate membership belongs to the target organization
- add by delegating to direct strategy with explicit context
- remove by ending the selected person-membership assignment only
