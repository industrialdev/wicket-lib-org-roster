# Backwards Compatibility

Released behavior in this library is expected to stay stable unless a breaking change is explicitly approved.

## Current Compatibility Rules

- keep `roster.strategy` defaulting to `direct`
- keep additive config keys opt-in
- do not silently change permission defaults
- keep `OrgManagement\OrgMan::get_instance()` as the theme-facing alias
- keep internal PHP APIs on camelCase naming
- keep upstream WordPress, WooCommerce, and Wicket helper names unchanged when they use underscores

## Additive Defaults Already in the Library

The following areas are additive and safe by default because they ship disabled or empty:

- `feature_flags.membership_resolution_prefer_current_cycle`
- `permissions.role_only_management_access.*`
- `ui.member_list.show_bulk_upload`
- `ui.member_list.display_roles_allowlist`
- `ui.member_list.display_roles_exclude`
- `ui.member_list.remove_policy_callout.*`
- `membership_cycle.*`

## What Counts As Breaking

- changing default strategy behavior
- changing default permissions in a way that expands access
- removing a documented config key that exists in `OrgManConfig`
- removing the `get_instance()` bridge alias
- changing process-handler request shapes in a way that breaks existing templates
