# IAA Example Overrides

IAA is a straightforward `groups` strategy example.

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
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
});
```
