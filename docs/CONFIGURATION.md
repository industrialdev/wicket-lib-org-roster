# Configuration

Source of truth: `src/Config/OrgManConfig.php`

Do not edit library defaults directly on a site. Override through:

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    return $config;
});
```

## Top-Level Namespaces

- `roster`
  - `strategy`: `direct`, `cascade`, `groups`, `membership_cycle`
- `feature_flags`
  - includes `membership_resolution_prefer_current_cycle`
- `roles`
  - canonical owner, manager, editor slugs
  - `aliases` for site-level role normalization
- `role_labels`
  - display labels for role slugs
- `permissions`
  - edit, manage, add, remove, purchase-seat gates
  - relationship-based permission mapping
  - owner-removal and owner-assignment guards
  - role-only management access override
- `member_addition`
  - base role, auto roles, stale-relationship repair, communication opt-in
- `cache`
  - transient on/off and TTL
- `relationships`
  - default type, add-flow type, allowlist, denylist, active-only card filtering
- `groups`
  - tag matching, manage roles, roster roles, removal mode, list sizes, group UI flags
- `membership_cycle`
  - strategy-local add/remove/purchase roles
  - explicit membership UUID requirement
- `additional_seats`
  - enabled, SKU, Gravity Form mapping, min/max quantity
- `documents`
  - allowed extensions and max size
- `business_info`
  - optional seat-limit info payload
- `seat_policy`
  - tier-to-seat overrides
- `ui`
  - organization list, member list, member view, member card field toggles
- `member_addition_form`
  - add-form layout, fields, permission-role filtering
- `bulk_upload`
  - CSV columns, relationship type rules, batch size
- `edit_permissions_modal`
  - allowed and excluded roles
- `member_edit`
  - active-membership requirement for role updates
- `notifications`
  - confirmation email sender
- `relationship_types`
  - custom and special labels

## Important Current Defaults

- default strategy: `direct`
- bulk upload UI: disabled
- unified member list: enabled
- unified member view: enabled
- additional seats: enabled
- membership-cycle mutation guard: explicit membership UUID required
- groups removal mode: `end_date`
- cache: disabled

## Current Notes

- `membership_cycle` is additive. It changes mutation scoping, not the page architecture.
- Site-specific docs under `docs/configs/` show example overrides only. They are not loaded automatically.
- Some docs in older revisions referenced config keys that do not exist in `OrgManConfig`. This file only documents keys present in the library today.
