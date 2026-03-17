# IAA Configuration

Source of truth: `../iaa-website-wordpress/src/web/app/themes/wicket-child/custom/orgroster.php`

## Active Strategy
- `roster.strategy = groups`

## Key Overrides
- `groups.tag_name = Roster Management`
- `groups.tag_case_sensitive = false`
- `groups.manage_roles = ['president', 'delegate', 'alternate_delegate', 'council_delegate', 'council_alternate_delegate', 'correspondent']`
- `groups.additional_info.key = association`
- `groups.additional_info.value_field = name`
- `groups.additional_info.fallback_to_org_uuid = true`

## Copy/Paste Config Function
```php
function wicket_child_orgman_config(array $config): array
{
    $config['roster']['strategy'] = 'groups';
    $config['groups']['tag_name'] = 'Roster Management';
    $config['groups']['tag_case_sensitive'] = false;
    $config['groups']['manage_roles'] = [
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
