# ESCRS Configuration

Date: 2026-03-03
Source of truth: `/Users/esteban/Dev/Projects-Wicket/escrs-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

## Active Strategy
- `roster.strategy = membership_cycle`

## Key Overrides
- Membership cycle permissions:
  - View/Add/Remove/Bulk Upload: `membership_owner`, `membership_manager`
  - Purchase seats: `membership_owner`
  - `prevent_owner_removal = true`
- Member management:
  - Explicit membership UUID required
  - Duplicate scope: `membership_uuid_active_only`
  - Removal mode: `end_date`
  - End date format: `Y-m-d\\T00:00:00P`
- Bulk upload:
  - Enabled
  - Allowed columns: `first_name`, `last_name`, `email`, `membership`
  - Membership mapping mode: `label_strict`
  - Invalid/duplicate handling: `skip_row`
  - Delete disabled
  - Max rows: `2000`
- Seat behavior:
  - Enforced per membership UUID
  - Capacity alert enabled with custom message/button
  - Checkout metadata requires membership UUID
- UI:
  - Unified member view/list enabled
  - Cycle tabs enabled, default tab `active`
  - Membership metadata shown
  - Table fields: `name`, `nominal`, `email`, `membership`, `membership_status`
  - Search fields: `name`, `email`
  - Page size: `15`
  - `search_clear_requires_submit = true`

## Copy/Paste Config Function
```php
function wicket_child_orgman_config(array $config): array
{
    $config['roster']['strategy'] = 'membership_cycle';

    $config['membership_cycle']['permissions']['view_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['add_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['remove_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['bulk_upload_roles'] = [
        'membership_owner',
        'membership_manager',
    ];
    $config['membership_cycle']['permissions']['purchase_seats_roles'] = [
        'membership_owner',
    ];
    $config['membership_cycle']['permissions']['prevent_owner_removal'] = true;

    $config['membership_cycle']['member_management']['require_explicit_membership_uuid'] = true;
    $config['membership_cycle']['member_management']['duplicate_scope']                  = 'membership_uuid_active_only';
    $config['membership_cycle']['member_management']['removal_mode']                     = 'end_date';
    $config['membership_cycle']['member_management']['removal_end_date_format']          = 'Y-m-d\\T00:00:00P';

    $config['membership_cycle']['bulk_upload']['enabled']         = true;
    $config['membership_cycle']['bulk_upload']['allowed_columns'] = [
        'first_name',
        'last_name',
        'email',
        'membership',
    ];
    $config['membership_cycle']['bulk_upload']['membership_column']     = 'membership';
    $config['membership_cycle']['bulk_upload']['membership_value_mode'] = 'label_strict';
    $config['membership_cycle']['bulk_upload']['on_invalid_membership'] = 'skip_row';
    $config['membership_cycle']['bulk_upload']['on_duplicate']          = 'skip_row';
    $config['membership_cycle']['bulk_upload']['allow_delete']          = false;
    $config['membership_cycle']['bulk_upload']['max_rows_per_file']     = 2000;
    $config['membership_cycle']['bulk_upload']['dry_run_default']       = false;

    $config['membership_cycle']['seats']['enforce_per_membership_uuid']                  = true;
    $config['membership_cycle']['seats']['show_capacity_alert']                          = true;
    $config['membership_cycle']['seats']['alert_message']                                = __('All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc');
    $config['membership_cycle']['seats']['purchase_button_label']                        = __('Purchase Additional Seats', 'wicket-acc');
    $config['membership_cycle']['seats']['require_membership_uuid_in_checkout_metadata'] = true;

    $config['membership_cycle']['ui']['use_unified_member_view']  = true;
    $config['membership_cycle']['ui']['use_unified_member_list']  = true;
    $config['membership_cycle']['ui']['show_cycle_tabs']          = true;
    $config['membership_cycle']['ui']['default_tab']              = 'active';
    $config['membership_cycle']['ui']['show_membership_metadata'] = true;
    $config['membership_cycle']['ui']['table_fields']             = [
        'name',
        'nominal',
        'email',
        'membership',
        'membership_status',
    ];
    $config['membership_cycle']['ui']['search_fields'] = [
        'name',
        'email',
    ];
    $config['membership_cycle']['ui']['page_size']                    = 15;
    $config['membership_cycle']['ui']['search_clear_requires_submit'] = true;

    return $config;
}
```
