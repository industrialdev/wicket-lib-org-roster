# Backwards Compatibility

Backwards compatibility is mandatory for all released (tagged) versions of this library.

Each repository tag represents a tested release that must remain compatible with behavior and defaults from earlier released tags, unless an explicit breaking-change release is approved.

Changes in unreleased code must not introduce breaking behavior for currently released versions.

Default configuration values that affect released behavior must not be changed silently.

Recent additive key:
- `ui.member_list.show_bulk_upload` was introduced with default `false` to preserve existing behavior unless explicitly enabled via config filter.
- `ui.member_list.account_status.*` was introduced with additive defaults so account-status copy/visibility can be customized without template changes.
- `ui.member_list.display_roles_allowlist` and `ui.member_list.display_roles_exclude` were introduced with empty-array defaults to preserve existing role-display behavior unless configured.

## Deprecation

Deprecated or compatibility layers may be removed only when both conditions are true:

1. The code/path was never part of a released tag.
2. Removal does not break compatibility guarantees for any released tag.

If a feature was present in a released tag, removal requires an explicit breaking-change decision and release plan.
