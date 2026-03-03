# IAA Configuration

Date: 2026-03-03
Source of truth: `/Users/esteban/Dev/Projects-Wicket/iaa-website-wordpress/src/web/app/themes/wicket-child/custom/orgroster.php`

## Active Strategy
- `roster.strategy = groups`

## Key Overrides
- Group tag configuration:
  - `groups.tag_name = Roster Management`
  - `groups.tag_case_sensitive = false`
- Management roles allowed through group strategy:
  - `president`
  - `delegate`
  - `alternate_delegate`
  - `council_delegate`
  - `council_alternate_delegate`
  - `correspondent`
- Additional info mapping:
  - `key = association`
  - `value_field = name`
  - `fallback_to_org_uuid = true`

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
