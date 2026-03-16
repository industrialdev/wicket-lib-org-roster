# MSA Example Overrides

MSA is a `cascade` strategy example that leans on current shared config keys.

```php
add_filter('wicket/acc/orgman/config', static function (array $config): array {
    $config['roster']['strategy'] = 'cascade';
    $config['feature_flags']['membership_resolution_prefer_current_cycle'] = true;

    $config['permissions']['manage_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = [];
    $config['permissions']['role_only_management_access']['enabled'] = true;
    $config['permissions']['role_only_management_access']['allowed_roles'] = [
        'membership_owner',
        'membership_manager',
    ];

    $config['member_addition_form']['fields']['permissions']['allowed_roles'] = ['org_editor'];
    $config['member_addition_form']['fields']['permissions']['excluded_roles'] = [
        'membership_manager',
        'membership_owner',
    ];
    $config['edit_permissions_modal']['allowed_roles'] = ['org_editor'];
    $config['edit_permissions_modal']['excluded_roles'] = [
        'membership_manager',
        'membership_owner',
    ];

    $config['member_edit']['require_active_membership_for_role_updates'] = true;
    $config['relationships']['default_type'] = 'regular_member';
    $config['relationships']['allowed_relationship_types'] = ['company_admin', 'regular_member'];
    $config['relationships']['exclude_relationship_types'] = ['affiliate'];
    $config['relationships']['member_card_active_only'] = true;
    $config['ui']['hide_relationship_type'] = false;

    $config['ui']['member_list']['show_remove_button'] = false;
    $config['ui']['member_list']['display_roles_allowlist'] = [
        'membership_owner',
        'membership_manager',
        'org_editor',
        'member',
    ];
    $config['ui']['member_list']['display_roles_exclude'] = [
        'supplemental_member',
        'cchlmembercommunity',
        'cchl_member_community',
    ];
    $config['ui']['member_list']['remove_policy_callout'] = [
        'enabled' => true,
        'placement' => 'above_members',
        'title' => __('Remove Members', 'wicket-acc'),
        'message' => __('To remove a member from your organization, please contact MSA directly.', 'wicket-acc'),
        'email' => 'associationmanagement@microscopy.org',
    ];
    $config['seat_policy']['tier_max_assignments'] = [
        'MAS Sustaining' => 3,
        'Sustaining' => 3,
        'Joint Sustaining' => 6,
    ];

    $config['relationship_types']['custom_types']['company_admin'] = __('Company Admin', 'wicket-acc');
    $config['relationship_types']['custom_types']['regular_member'] = __('Regular Member', 'wicket-acc');
    $config['relationship_types']['custom_types']['affiliate'] = __('Affiliate', 'wicket-acc');

    return $config;
});
```
