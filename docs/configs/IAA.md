# IAA Configuration

Source of truth: `../iaa-website-wordpress/src/web/app/themes/wicket-child/custom/orgroster.php`

This document mirrors the current site override. If it drifts, update the site config first, then update this file.

## Active Strategy

- `membership.strategy = groups`

## Current Override Paths

### `membership`

- `membership.strategy = groups`

### `groups`

- `groups.matching.tag_name = Roster Management`
- `groups.matching.tag_case_sensitive = false`
- `groups.roles.management = ['president', 'delegate', 'alternate_delegate', 'council_delegate', 'council_alternate_delegate', 'correspondent']`
- `groups.additional_info.key = association`
- `groups.additional_info.value_field = name`
- `groups.additional_info.fallback_to_org_uuid = true`
- `groups.ui.add_member_auto_close_on_success = true`
- `groups.ui.add_member_auto_close_delay_seconds = 7`
- `groups.removal.end_date_anchor = day_start_utc`
- `groups.presentation.add_member_auto_close_on_success = true`
- `groups.presentation.add_member_auto_close_delay_seconds = 7`

## Current Config Function

```php
function wicket_child_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'groups';
    $config['groups']['matching']['tag_name'] = 'Roster Management';
    $config['groups']['matching']['tag_case_sensitive'] = false;
    $config['groups']['roles']['management'] = [
        'president',
        'delegate',
        'alternate_delegate',
        'council_delegate',
        'council_alternate_delegate',
        'correspondent',
    ];
    $config['groups']['additional_info'] = [
        'key' => 'association',
        'value_field' => 'name',
        'fallback_to_org_uuid' => true,
    ];
    $config['groups']['ui']['add_member_auto_close_on_success'] = true;
    $config['groups']['ui']['add_member_auto_close_delay_seconds'] = 7;
    $config['groups']['removal']['end_date_anchor'] = 'day_start_utc';
    $config['groups']['presentation']['add_member_auto_close_on_success'] = true;
    $config['groups']['presentation']['add_member_auto_close_delay_seconds'] = 7;

    return $config;
}
```
