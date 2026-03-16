# NJBIA Example Overrides

NJBIA is a `cascade` strategy example with relationship editing and bulk upload enabled.

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['roster']['strategy'] = 'cascade';
    $config['relationships']['default_type'] = 'member_contact';
    $config['relationship_types']['custom_types']['employee_staff'] = __('Employee', 'wicket-acc');
    $config['relationship_types']['custom_types']['grade_4'] = __('Grade 4', 'wicket-acc');

    $config['member_addition_form']['layout'] = 'simplified';
    $config['member_addition_form']['fields']['relationship_type']['enabled'] = true;
    $config['member_addition_form']['fields']['description']['label'] = __('Job Title', 'wicket-acc');
    $config['member_addition_form']['fields']['description']['input_type'] = 'text';
    $config['member_addition_form']['allow_relationship_type_editing'] = true;

    $config['permissions']['add_members'] = ['membership_manager'];
    $config['permissions']['remove_members'] = ['membership_manager'];
    $config['permissions']['manage_members'] = ['membership_manager'];
    $config['permissions']['prevent_owner_removal'] = true;
    $config['permissions']['relationship_based_permissions'] = true;

    $config['ui']['show_special_relationships'] = true;
    $config['ui']['hide_relationship_type'] = false;
    $config['ui']['member_card_fields']['relationship_type']['enabled'] = true;
    $config['ui']['member_card_fields']['job_title']['enabled'] = false;
    $config['ui']['member_card_fields']['description']['enabled'] = true;
    $config['ui']['member_list']['show_bulk_upload'] = true;

    return $config;
});
```
