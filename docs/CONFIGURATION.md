# Configuration Reference

This library is configured through `\OrgManagement\Config\OrgManConfig::get()` in `src/Config/OrgManConfig.php`.

End-user developers should not update or modify `src/Config/OrgManConfig.php` directly.
Use the centralized `wicket/acc/orgman/config` filter to configure behavior without changing library source files:

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    // override keys here
    return $config;
});
```

## Complete Configuration Map

All keys below are current defaults from `src/Config/OrgManConfig.php`.

### `roster`
| Key | Default | Type | Description |
|---|---|---|---|
| `roster.strategy` | `direct` | string | Selects the roster management strategy used across the plugin (`direct`, `cascade`, `groups`, `membership_cycle`). |

### `roles`
| Key | Default | Type | Description |
|---|---|---|---|
| `roles.owner` | `membership_owner` | string | Canonical role slug treated as the organization owner role in internal checks. |
| `roles.manager` | `membership_manager` | string | Canonical role slug treated as the manager role in internal checks. |
| `roles.editor` | `org_editor` | string | Canonical role slug treated as the editor role in internal checks. |

### `role_labels`
| Key | Default | Type | Description |
|---|---|---|---|
| `role_labels.membership_manager` | `Membership Manager` | string | Human-friendly display label for `membership_manager` in UI role selectors/lists. |
| `role_labels.org_editor` | `Org Editor` | string | Human-friendly display label for `org_editor` in UI role selectors/lists. |
| `role_labels.membership_owner` | `Membership Owner` | string | Human-friendly display label for `membership_owner` in UI role selectors/lists. |
| `role_labels.Cchlmembercommunity` | `CCHL Member Community` | string | Display label override for uppercase/camel role slug variants returned by upstream data. |
| `role_labels.cchlmembercommunity` | `CCHL Member Community` | string | Display label override for lowercase role slug variants returned by upstream data. |

### `permissions`
| Key | Default | Type | Description |
|---|---|---|---|
| `permissions.edit_organization` | `['org_editor']` | array | Role slugs allowed to edit organization profile/details. |
| `permissions.manage_members` | `['membership_manager','membership_owner']` | array | Role slugs with general member management privileges. |
| `permissions.add_members` | `['membership_manager','membership_owner']` | array | Role slugs allowed to add members to an organization. |
| `permissions.remove_members` | `['membership_manager','membership_owner']` | array | Role slugs allowed to remove members from an organization. |
| `permissions.purchase_seats` | `['membership_owner','membership_manager','org_editor']` | array | Role slugs allowed to purchase additional seats. |
| `permissions.any_management` | `['org_editor','membership_manager','membership_owner']` | array | Broad management role list used for coarse access checks. |
| `permissions.prevent_owner_removal` | `false` | bool | When true, blocks removing users with owner-level role assignments in non-cycle flows. |
| `permissions.relationship_based_permissions` | `false` | bool | When true, role assignment is driven by relationship type mappings in `relationship_roles_map`. |
| `permissions.prevent_owner_assignment` | `true` | bool | When true, hides/blocks assignment of the owner role from UI mutation flows. |
| `permissions.role_only_management_access.enabled` | `false` | bool | When true, users with allowed org roles may access organization-management screens without an active org membership. |
| `permissions.role_only_management_access.allowed_roles` | `['membership_owner']` | array | Role allowlist used by role-only management access. Allowed roles can access org-management visibility surfaces (org list, organization profile link, and bulk-upload entry points when bulk upload is enabled), and still use required-role intersections for mutation gates (add/remove/purchase). |
| `permissions.relationship_roles_map.ceo` | `['org_editor','membership_manager']` | array | Roles auto-assigned when relationship type is `ceo` and relationship-based permissions are enabled. |
| `permissions.relationship_roles_map.primary_hr_contact` | `['org_editor','membership_manager']` | array | Roles auto-assigned for `primary_hr_contact` relationship type. |
| `permissions.relationship_roles_map.member_contact` | `['org_editor','membership_manager']` | array | Roles auto-assigned for `member_contact` relationship type. |
| `permissions.relationship_roles_map.employee_staff` | `[]` | array | Roles auto-assigned for `employee_staff`; empty means no automatic role additions. |
| `permissions.relationship_roles_map.advertising_sponsor_contact` | `[]` | array | Roles auto-assigned for `advertising_sponsor_contact`; empty means no additions. |
| `permissions.relationship_roles_map.advertising_sponsor_billing` | `[]` | array | Roles auto-assigned for `advertising_sponsor_billing`; empty means no additions. |

### `member_addition`
| Key | Default | Type | Description |
|---|---|---|---|
| `member_addition.auto_assign_roles` | `['supplemental_member','CCHL Member Community']` | array | Role slugs automatically attached to newly added members. |
| `member_addition.base_member_role` | `member` | string | Base membership role/type applied when creating member assignments. |
| `member_addition.auto_opt_in_communications.enabled` | `true` | bool | Master toggle for auto-opting newly added members into communication preferences. |
| `member_addition.auto_opt_in_communications.email` | `true` | bool | If auto opt-in is enabled, controls whether email opt-in is set. |
| `member_addition.auto_opt_in_communications.sublists` | `['one','two','three','four','five']` | array | Communication sublist identifiers to auto-subscribe when auto opt-in runs. |

### `cache`
| Key | Default | Type | Description |
|---|---|---|---|
| `cache.enabled` | `false` | bool | Enables transient caching in services that honor this config (mostly for performance control/testing). |
| `cache.duration` | `300` | int | Transient cache TTL in seconds where caching is enabled. |

### `relationships`
| Key | Default | Type | Description |
|---|---|---|---|
| `relationships.default_type` | `Position` | string | Default relationship type label/value used when creating person-to-organization connections. |
| `relationships.member_addition_type` | `position` | string | Normalized relationship type slug used by roster member-add flows. |
| `relationships.allowed_relationship_types` | `[]` | array | Allowlist of relationship type slugs for editing/selection; empty means allow all. |
| `relationships.exclude_relationship_types` | `[]` | array | Denylist of relationship type slugs removed from editing/selection; empty means none excluded. |
| `relationships.member_card_active_only` | `false` | bool | When true, member cards only display active person-to-organization relationships for the current organization. |

Relationship matching in member-card filtering is normalized before comparison (for example, casing/spacing variants, `affiliation` aliases, and `company admin`/`regular member` textual variants are normalized to canonical slugs).

### `groups`
| Key | Default | Type | Description |
|---|---|---|---|
| `groups.tag_name` | `Roster Management` | string | Tag name used to identify groups participating in group-based roster management. |
| `groups.tag_case_sensitive` | `false` | bool | Controls case-sensitive matching for `groups.tag_name` filtering. |
| `groups.manage_roles` | `['president','delegate','alternate_delegate','council_delegate','council_alternate_delegate','correspondent']` | array | Group membership role slugs that grant permission to manage group roster entries. |
| `groups.roster_roles` | `['member','observer']` | array | Group role slugs available for roster member assignments. |
| `groups.member_role` | `member` | string | Canonical "member" group role slug used by UI and write operations. |
| `groups.observer_role` | `observer` | string | Canonical "observer" group role slug used by UI and write operations. |
| `groups.seat_limited_roles` | `['member']` | array | Group roles that count toward seat-limited capacity checks. |
| `groups.list.page_size` | `20` | int | Fetch page size for resolving group-membership listings in groups mode. |
| `groups.list.member_page_size` | `15` | int | Default number of group members returned/displayed per page in group member lists. |
| `groups.additional_info.key` | `association` | string | Key expected inside group `custom_data_field` for organization association metadata. |
| `groups.additional_info.value_field` | `name` | string | Nested field extracted from additional info value payload when resolving organization linkage. |
| `groups.additional_info.fallback_to_org_uuid` | `true` | bool | If additional-info mapping fails, fallback to the direct organization UUID relationship. |
| `groups.removal.mode` | `end_date` | string | Removal behavior for group memberships (`end_date` soft close vs `delete` hard delete). |
| `groups.removal.end_date_format` | `Y-m-d\T00:00:00P` | string | Date format used when writing end dates in `end_date` removal mode. |
| `groups.ui.enable_group_profile_edit` | `true` | bool | Enables inline editing controls in group profile views. |
| `groups.ui.use_unified_member_list` | `true` | bool | Uses unified member list rendering path for group members. |
| `groups.ui.use_unified_member_view` | `true` | bool | Uses unified member card/view rendering path for group members. |
| `groups.ui.show_edit_permissions` | `false` | bool | Shows/hides edit-permissions controls in group member interfaces. |
| `groups.ui.search_clear_requires_submit` | `true` | bool | Requires submit action to clear search state in group-scoped unified views. |
| `groups.ui.editable_fields` | `['name','description']` | array | Group profile attribute keys that are editable in the UI. |

### `membership_cycle`
| Key | Default | Type | Description |
|---|---|---|---|
| `membership_cycle.strategy_key` | `membership_cycle` | string | Internal strategy identifier used for cycle-scoped roster behavior. |
| `membership_cycle.permissions.add_roles` | `['membership_manager']` | array | Role slugs allowed to add members when `membership_cycle` strategy is active. |
| `membership_cycle.permissions.remove_roles` | `['membership_manager']` | array | Role slugs allowed to remove members when `membership_cycle` strategy is active. |
| `membership_cycle.permissions.purchase_seats_roles` | `['membership_owner','membership_manager','org_editor']` | array | Role slugs allowed to purchase seats in membership-cycle mode. |
| `membership_cycle.permissions.prevent_owner_removal` | `true` | bool | Extra guard in cycle mode to block owner removal flows. |
| `membership_cycle.member_management.require_explicit_membership_uuid` | `true` | bool | Requires an explicit membership UUID for cycle-scoped member mutation endpoints. |

### `additional_seats`
| Key | Default | Type | Description |
|---|---|---|---|
| `additional_seats.enabled` | `true` | bool | Master feature flag for additional seats purchase flow. |
| `additional_seats.sku` | `additional-seats` | string | WooCommerce SKU used to resolve the additional seats product. |
| `additional_seats.form_id` | `0` | int | Gravity Forms ID used for purchase flow; `0` triggers slug-based auto-detection. |
| `additional_seats.form_slug` | `additional-seats` | string | Gravity Forms slug used when `form_id` is `0`. |
| `additional_seats.min_quantity` | `1` | int | Minimum quantity allowed when adding seat products to cart. |
| `additional_seats.max_quantity` | `900` | int | Maximum quantity allowed when adding seat products to cart. |

### `documents`
| Key | Default | Type | Description |
|---|---|---|---|
| `documents.allowed_types` | `['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif']` | array | File extensions permitted for organization document uploads. |
| `documents.max_size` | `10485760` | int | Maximum upload size in bytes for organization documents. |

### `business_info`
| Key | Default | Type | Description |
|---|---|---|---|
| `business_info.seat_limit_info` | `null` | mixed | Optional seat-limit informational payload shown in business info UI (`null` disables). |

### `seat_policy`
| Key | Default | Type | Description |
|---|---|---|---|
| `seat_policy.tier_max_assignments` | `[]` | array | Optional mapping of membership tier names to max seat assignments; when empty, seat limits use API `max_assignments`. |
| `seat_policy.tier_name_case_sensitive` | `false` | bool | Controls whether `seat_policy.tier_max_assignments` key matching is case-sensitive. |

### `ui`
| Key | Default | Type | Description |
|---|---|---|---|
| `ui.organization_list.page_size` | `5` | int | Number of organizations shown per page in non-groups organization index lists. |
| `ui.hide_relationship_type` | `true` | bool | Hides relationship type text in legacy/member card presentation. |
| `ui.show_special_relationships` | `false` | bool | Shows special relationship types (e.g. exchange-defined) in member-facing UI. |
| `ui.member_list.use_unified` | `true` | bool | Enables unified member list component instead of legacy list rendering. |
| `ui.member_list.show_edit_permissions` | `true` | bool | Shows edit-permissions action in organization member list views. |
| `ui.member_list.show_remove_button` | `true` | bool | Shows remove-member actions in organization member lists when user permissions also allow removal. |
| `ui.member_list.show_bulk_upload` | `false` | bool | Enables CSV bulk-upload panel in organization member views (non-groups modes only) when the user can add members. |
| `ui.member_list.display_roles_allowlist` | `[]` | array | Optional role-slug allowlist for role labels shown on member cards/lists; empty means all roles can display. |
| `ui.member_list.display_roles_exclude` | `[]` | array | Optional role-slug denylist removed from role labels shown on member cards/lists. |
| `ui.member_list.account_status.enabled` | `true` | bool | Toggles account-confirmation status indicators on member cards/lists. |
| `ui.member_list.account_status.show_unconfirmed_label` | `true` | bool | Controls whether unconfirmed accounts display inline helper text beside the status dot. |
| `ui.member_list.account_status.confirmed_tooltip` | `Account confirmed` | string | Tooltip text for confirmed account status indicators. |
| `ui.member_list.account_status.unconfirmed_tooltip` | `Account not confirmed` | string | Tooltip text for unconfirmed account status indicators. |
| `ui.member_list.account_status.unconfirmed_label` | `Account not confirmed` | string | Inline label text shown for unconfirmed accounts when `show_unconfirmed_label` is enabled. |
| `ui.member_list.seat_limit_message` | `All seats have been assigned. Please purchase additional seats to add more members.` | string | Message shown in member-list views when seat capacity is full and add-member action is hidden. |
| `ui.member_list.remove_policy_callout.enabled` | `false` | bool | Enables an informational callout shown when remove actions are disabled/hidden. |
| `ui.member_list.remove_policy_callout.placement` | `above_members` | string | Controls callout placement when enabled and remove actions are hidden (`above_members` or `below_members`). |
| `ui.member_list.remove_policy_callout.title` | `Remove Members` | string | Optional callout title displayed above the remove-policy message. |
| `ui.member_list.remove_policy_callout.message` | `To remove a member from your organization, please contact your association directly.` | string | Callout body message for removal policy instructions. |
| `ui.member_list.remove_policy_callout.email` | `` | string | Optional support/contact email rendered as a `mailto:` link in the remove-policy callout. |
| `ui.member_view.use_unified` | `true` | bool | Enables unified member card/view component instead of legacy view rendering. |
| `ui.member_view.search_clear_requires_submit` | `false` | bool | Controls whether clearing member search triggers immediate refresh or requires submit. |
| `ui.member_card_fields.name.enabled` | `true` | bool | Toggles display of member name field on member cards. |
| `ui.member_card_fields.name.label` | `Name` | string | UI label text for member name field. |
| `ui.member_card_fields.job_title.enabled` | `true` | bool | Toggles display of member job title field on member cards. |
| `ui.member_card_fields.job_title.label` | `Job Title` | string | UI label text for member job title field. |
| `ui.member_card_fields.description.enabled` | `true` | bool | Toggles display of member description field on member cards. |
| `ui.member_card_fields.description.label` | `Description` | string | UI label text for member description field. |
| `ui.member_card_fields.description.input_type` | `textarea` | string | Preferred input widget type for editable description values. |
| `ui.member_card_fields.email.enabled` | `true` | bool | Toggles display of member email field on member cards. |
| `ui.member_card_fields.email.label` | `Email` | string | UI label text for member email field. |
| `ui.member_card_fields.roles.enabled` | `true` | bool | Toggles display of assigned role list on member cards. |
| `ui.member_card_fields.roles.label` | `Roles` | string | UI label text for assigned member roles. |
| `ui.member_card_fields.relationship_type.enabled` | `false` | bool | Toggles display of relationship type on member cards. |
| `ui.member_card_fields.relationship_type.label` | `Relationship` | string | UI label text for relationship type field. |

### `member_addition_form`
| Key | Default | Type | Description |
|---|---|---|---|
| `member_addition_form.layout` | `full` | string | Selects the Add Member form layout variant (`full` or simplified alternatives). |
| `member_addition_form.fields.first_name.enabled` | `true` | bool | Shows/hides first-name input field in Add Member form. |
| `member_addition_form.fields.first_name.required` | `true` | bool | Marks first-name field as required in Add Member form validation/UI. |
| `member_addition_form.fields.first_name.label` | `First Name` | string | UI label text for first-name field. |
| `member_addition_form.fields.last_name.enabled` | `true` | bool | Shows/hides last-name input field in Add Member form. |
| `member_addition_form.fields.last_name.required` | `true` | bool | Marks last-name field as required in Add Member form validation/UI. |
| `member_addition_form.fields.last_name.label` | `Last Name` | string | UI label text for last-name field. |
| `member_addition_form.fields.email.enabled` | `true` | bool | Shows/hides email input field in Add Member form. |
| `member_addition_form.fields.email.required` | `true` | bool | Marks email field as required in Add Member form validation/UI. |
| `member_addition_form.fields.email.label` | `Email Address` | string | UI label text for email field. |
| `member_addition_form.fields.relationship_type.enabled` | `false` | bool | Shows/hides relationship-type selector in Add Member form. |
| `member_addition_form.fields.relationship_type.required` | `false` | bool | Marks relationship-type field as required when enabled. |
| `member_addition_form.fields.relationship_type.label` | `Relationship Type` | string | UI label text for relationship-type field. |
| `member_addition_form.fields.description.enabled` | `true` | bool | Shows/hides description input field in Add Member form. |
| `member_addition_form.fields.description.required` | `false` | bool | Marks description field as required when enabled. |
| `member_addition_form.fields.description.label` | `Description` | string | UI label text for description field. |
| `member_addition_form.fields.description.input_type` | `textarea` | string | Preferred input widget type for description field. |
| `member_addition_form.fields.permissions.enabled` | `true` | bool | Shows/hides permissions (role assignment) input in Add Member form. |
| `member_addition_form.fields.permissions.required` | `true` | bool | Marks permissions selection as required when enabled. |
| `member_addition_form.fields.permissions.label` | `Permissions` | string | UI label text for permissions field. |
| `member_addition_form.fields.permissions.allowed_roles` | `[]` | array | Allowlist of assignable roles in Add Member form; empty means all available roles. |
| `member_addition_form.fields.permissions.excluded_roles` | `['Cchlmembercommunity','cchlmembercommunity']` | array | Denylist of roles hidden from Add Member permissions selector. |
| `member_addition_form.allow_relationship_type_editing` | `false` | bool | Allows relationship-type edits after member creation in permissions/edit flows. |

### `edit_permissions_modal`
| Key | Default | Type | Description |
|---|---|---|---|
| `edit_permissions_modal.allowed_roles` | `[]` | array | Allowlist of roles that can be assigned in Edit Permissions modal; empty means all. |
| `edit_permissions_modal.excluded_roles` | `['Cchlmembercommunity','cchlmembercommunity']` | array | Denylist of roles hidden from Edit Permissions modal choices. |

### `member_edit`
| Key | Default | Type | Description |
|---|---|---|---|
| `member_edit.require_active_membership_for_role_updates` | `false` | bool | When true, blocks role updates for inactive person-membership records in backend edit-permissions processing. |

### `notifications`
| Key | Default | Type | Description |
|---|---|---|---|
| `notifications.confirmation_email_from` | `cchl@wicketcloud.com` | string | Informational sender email shown in account confirmation/help messaging. |

### `relationship_types`
| Key | Default | Type | Description |
|---|---|---|---|
| `relationship_types.custom_types.ceo` | `CEO` | string | Display label for custom relationship type slug `ceo`. |
| `relationship_types.custom_types.primary_hr_contact` | `Primary HR Contact` | string | Display label for custom relationship type slug `primary_hr_contact`. |
| `relationship_types.custom_types.employee_staff` | `Employee` | string | Display label for custom relationship type slug `employee_staff`. |
| `relationship_types.custom_types.member_contact` | `Member Contact` | string | Display label for custom relationship type slug `member_contact`. |
| `relationship_types.special_types.advertising_sponsor_contact` | `Advertising/Sponsor Contact` | string | Display label for special relationship type shown when special relationships are enabled. |
| `relationship_types.special_types.advertising_sponsor_billing` | `Advertising/Sponsor Billing Contact` | string | Display label for special relationship type shown when special relationships are enabled. |

## Related Non-Array Filters

These are not in the config array, but are used by runtime path/url resolution.

| Filter | Description |
|---|---|
| `wicket/acc/orgman/base_path` | Overrides filesystem base path used to resolve plugin assets/templates at runtime. Default auto-detects the package root. |
| `wicket/acc/orgman/base_url` | Overrides base URL used to enqueue/access public assets at runtime. Default auto-resolves from either `WP_CONTENT_DIR` (content URL) or `ABSPATH` (site URL, including root `vendor/...` installs). |

## Strategy Setup Examples

Use one of these as a baseline in a site plugin or theme `functions.php`.

### Direct Strategy Baseline

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    $config['roster']['strategy'] = 'direct';

    // Common direct-mode permission baseline.
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['relationship_based_permissions'] = false;
    $config['ui']['member_list']['show_bulk_upload'] = true;

    return $config;
});
```

### Optional: Role-Only Management Access (Site Override)

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    // Library default is disabled. Enable only when business rules require it.
    $config['permissions']['role_only_management_access']['enabled'] = true;
    $config['permissions']['role_only_management_access']['allowed_roles'] = ['membership_owner'];

    return $config;
});
```

When enabled, allowed roles can see organization-management surfaces even without active membership entries, including the organization profile link and bulk-upload navigation (when `ui.member_list.show_bulk_upload = true`).

### Cascade Strategy Baseline

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    $config['roster']['strategy'] = 'cascade';

    // Cascade commonly uses relationship-driven role assignment.
    $config['permissions']['relationship_based_permissions'] = true;
    $config['permissions']['relationship_roles_map']['ceo'] = ['org_editor', 'membership_manager'];
    $config['permissions']['relationship_roles_map']['primary_hr_contact'] = ['org_editor', 'membership_manager'];

    return $config;
});
```

### Groups Strategy Baseline

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    $config['roster']['strategy'] = 'groups';

    // Group roster behavior.
    $config['groups']['tag_name'] = 'Roster Management';
    $config['groups']['manage_roles'] = [
        'president',
        'delegate',
        'alternate_delegate',
    ];
    $config['groups']['roster_roles'] = ['member', 'observer'];
    $config['groups']['seat_limited_roles'] = ['member'];
    $config['groups']['removal']['mode'] = 'end_date'; // or 'delete'

    // UI defaults often desired in groups mode.
    $config['groups']['ui']['use_unified_member_list'] = true;
    $config['groups']['ui']['use_unified_member_view'] = true;

    return $config;
});
```

### Membership Cycle Strategy Baseline

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    $config['roster']['strategy'] = 'membership_cycle';

    // Strategy-local permission controls.
    $config['membership_cycle']['permissions']['add_roles'] = ['membership_manager'];
    $config['membership_cycle']['permissions']['remove_roles'] = ['membership_manager'];
    $config['membership_cycle']['permissions']['purchase_seats_roles'] = [
        'membership_owner',
        'membership_manager',
        'org_editor',
    ];
    $config['membership_cycle']['permissions']['prevent_owner_removal'] = true;
    $config['membership_cycle']['member_management']['require_explicit_membership_uuid'] = true;

    return $config;
});
```
