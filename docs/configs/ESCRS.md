# ESCRS Configuration

Source of truth: `../escrs-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

## Active Strategy

- `membership.strategy = membership_cycle`

## Canonical Overrides

### `membership`

- `membership.strategy = membership_cycle`
- `membership.cycle.permissions.add_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.remove_member_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.purchase_seat_roles = ['membership_owner']`
- `membership.cycle.prevent_owner_removal = true`
- `membership.cycle.require_explicit_membership_uuid = true`

### `presentation`

- `presentation.member_view.search_clear_requires_submit = true`

## Site Extensions To Carry Forward

These settings are site-specific today. They are grouped under the most logical canonical area, but the library does not currently consume them all.

### `membership`

- `membership.cycle.permissions.view_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.permissions.bulk_upload_roles = ['membership_owner', 'membership_manager']`
- `membership.cycle.member_management.duplicate_scope = membership_uuid_active_only`
- `membership.cycle.member_management.removal_mode = end_date`
- `membership.cycle.member_management.removal_end_date_format = Y-m-d\T00:00:00P`
- `membership.cycle.seats.enforce_per_membership_uuid = true`
- `membership.cycle.seats.show_capacity_alert = true`
- `membership.cycle.seats.alert_message = All seats have been assigned. Please purchase additional seats to add more members.`
- `membership.cycle.seats.purchase_button_label = Purchase Additional Seats`
- `membership.cycle.seats.require_membership_uuid_in_checkout_metadata = true`

### `member_management`

- `member_management.bulk_upload.enabled = true`
- `member_management.bulk_upload.allowed_columns = ['first_name', 'last_name', 'email', 'membership']`
- `member_management.bulk_upload.membership_column = membership`
- `member_management.bulk_upload.membership_value_mode = label_strict`
- `member_management.bulk_upload.on_invalid_membership = skip_row`
- `member_management.bulk_upload.on_duplicate = skip_row`
- `member_management.bulk_upload.allow_delete = false`
- `member_management.bulk_upload.max_rows_per_file = 2000`
- `member_management.bulk_upload.dry_run_default = false`

### `presentation`

- `presentation.member_list.use_unified = true`
- `presentation.member_view.use_unified = true`
- `presentation.member_view.cycle_tabs.enabled = true`
- `presentation.member_view.cycle_tabs.default_tab = active`
- `presentation.member_view.show_membership_metadata = true`
- `presentation.member_view.table_fields = ['name', 'nominal', 'email', 'membership', 'membership_status']`
- `presentation.member_view.search_fields = ['name', 'email']`
- `presentation.member_view.page_size = 15`

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `membership_cycle.permissions.add_roles -> membership.cycle.permissions.add_member_roles`
- `membership_cycle.permissions.remove_roles -> membership.cycle.permissions.remove_member_roles`
- `membership_cycle.permissions.purchase_seats_roles -> membership.cycle.permissions.purchase_seat_roles`
- `membership_cycle.permissions.prevent_owner_removal -> membership.cycle.prevent_owner_removal`
- `membership_cycle.member_management.require_explicit_membership_uuid -> membership.cycle.require_explicit_membership_uuid`
- `membership_cycle.ui.search_clear_requires_submit -> presentation.member_view.search_clear_requires_submit`
- All other `membership_cycle.*` keys listed above remain site extensions pending library support.

## Copy/Paste Config Function

```php
function wicket_child_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'membership_cycle';
    $config['membership']['cycle']['permissions']['add_member_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['permissions']['remove_member_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['permissions']['purchase_seat_roles'] = [
        'membership_owner',
    ];
    $config['membership']['cycle']['prevent_owner_removal'] = true;
    $config['membership']['cycle']['require_explicit_membership_uuid'] = true;

    $config['presentation']['member_view']['search_clear_requires_submit'] = true;

    // Site extensions not yet standardized in the library.
    $config['membership']['cycle']['permissions']['view_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['permissions']['bulk_upload_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership']['cycle']['member_management']['duplicate_scope'] = 'membership_uuid_active_only';
    $config['membership']['cycle']['member_management']['removal_mode'] = 'end_date';
    $config['membership']['cycle']['member_management']['removal_end_date_format'] = 'Y-m-d\\T00:00:00P';
    $config['membership']['cycle']['seats']['enforce_per_membership_uuid'] = true;
    $config['membership']['cycle']['seats']['show_capacity_alert'] = true;
    $config['membership']['cycle']['seats']['alert_message'] = __('All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc');
    $config['membership']['cycle']['seats']['purchase_button_label'] = __('Purchase Additional Seats', 'wicket-acc');
    $config['membership']['cycle']['seats']['require_membership_uuid_in_checkout_metadata'] = true;

    $config['member_management']['bulk_upload']['enabled'] = true;
    $config['member_management']['bulk_upload']['allowed_columns'] = [
        'first_name',
        'last_name',
        'email',
        'membership',
    ];
    $config['member_management']['bulk_upload']['membership_column'] = 'membership';
    $config['member_management']['bulk_upload']['membership_value_mode'] = 'label_strict';
    $config['member_management']['bulk_upload']['on_invalid_membership'] = 'skip_row';
    $config['member_management']['bulk_upload']['on_duplicate'] = 'skip_row';
    $config['member_management']['bulk_upload']['allow_delete'] = false;
    $config['member_management']['bulk_upload']['max_rows_per_file'] = 2000;
    $config['member_management']['bulk_upload']['dry_run_default'] = false;

    $config['presentation']['member_list']['use_unified'] = true;
    $config['presentation']['member_view']['use_unified'] = true;
    $config['presentation']['member_view']['cycle_tabs']['enabled'] = true;
    $config['presentation']['member_view']['cycle_tabs']['default_tab'] = 'active';
    $config['presentation']['member_view']['show_membership_metadata'] = true;
    $config['presentation']['member_view']['table_fields'] = [
        'name',
        'nominal',
        'email',
        'membership',
        'membership_status',
    ];
    $config['presentation']['member_view']['search_fields'] = [
        'name',
        'email',
    ];
    $config['presentation']['member_view']['page_size'] = 15;

    return $config;
}
```
