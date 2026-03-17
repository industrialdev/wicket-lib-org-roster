# IAA Configuration

Source of truth: `../iaa-website-wordpress/src/web/app/themes/wicket-child/custom/orgroster.php`

## Active Strategy

- `membership.strategy = groups`

## Canonical Overrides

### `membership`

- `membership.strategy = groups`

### `groups`

- `groups.matching.tag_name = Roster Management`
- `groups.matching.tag_case_sensitive = false`
- `groups.roles.management = ['president', 'delegate', 'alternate_delegate', 'council_delegate', 'council_alternate_delegate', 'correspondent']`
- `groups.additional_info.key = association`
- `groups.additional_info.value_field = name`
- `groups.additional_info.fallback_to_org_uuid = true`

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `groups.tag_name -> groups.matching.tag_name`
- `groups.tag_case_sensitive -> groups.matching.tag_case_sensitive`
- `groups.manage_roles -> groups.roles.management`
- `groups.additional_info.* -> groups.additional_info.*`

## Copy/Paste Config Function

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

    return $config;
}
```
