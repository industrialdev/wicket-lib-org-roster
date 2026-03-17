# MSA Configuration

Source of truth: `../msa-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

## Active Strategy
- `roster.strategy = cascade`

## Key Overrides
- Feature flags:
  - `membership_resolution_prefer_current_cycle = true`
- Member addition:
  - `auto_assign_roles = []`
- Permissions:
  - `manage_members = ['membership_manager', 'membership_owner']`
  - `add_members = ['membership_manager', 'membership_owner']`
  - `remove_members = []`
  - Role-only management access enabled for `membership_owner`, `membership_manager`
  - `prevent_owner_assignment = true`
- Add/edit permissions UI:
  - Add form permissions allowed roles: `['org_editor']`
  - Add form permissions excluded roles: `['membership_manager', 'membership_owner']`
  - Edit modal allowed roles: `['org_editor']`
  - Edit modal excluded roles: `['membership_manager', 'membership_owner']`
- Member addition form:
  - `first_name`, `last_name`, `email` enabled and required
  - `description` disabled
  - `permissions` enabled and not required
  - `relationship_type` disabled
- Member edit:
  - `require_active_membership_for_role_updates = true`
- Relationships:
  - `default_type = regular_member`
  - `allowed_relationship_types = ['company_admin', 'regular_member']`
  - `exclude_relationship_types = ['affiliate']`
  - `member_card_active_only = true`
  - Organization list includes active connections
  - Organization list active connection types: `['company_admin']`
  - Management access via active connections enabled for `['company_admin']`
- UI:
  - `hide_relationship_type = false`
  - Unified member list and member view enabled
  - Remove button hidden
  - Bulk upload hidden
  - Display-role allowlist: `membership_owner`, `membership_manager`, `org_editor`, `member`
  - Display-role exclude list: `supplemental_member`, `cchlmembercommunity`, `cchl_member_community`
  - Account-status column enabled with custom tooltips/labels
  - Seat-limit message customized
  - Remove-policy callout enabled above members with MSA contact email
- Seat policy:
  - `MAS Sustaining = 3`
  - `Sustaining = 3`
  - `Joint Sustaining = 6`
- Relationship labels:
  - `company_admin => Company Admin`
  - `regular_member => Regular Member`
  - `affiliate => Affiliate`
- Notifications:
  - `confirmation_email_from = associationmanagement@microscopy.org`
  - Custom `person_to_org_assignment` and `group_assignment` email subject/body templates

## Copy/Paste Config Function
```php
function wicket_child_orgman_config(array $config): array
{
    // Strategy
    $config['roster']['strategy'] = 'cascade';
    $config['feature_flags']['membership_resolution_prefer_current_cycle'] = true;
    $config['member_addition']['auto_assign_roles'] = [];

    // Management permissions
    $config['permissions']['manage_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = [];
    $config['permissions']['role_only_management_access']['enabled'] = true;
    $config['permissions']['role_only_management_access']['allowed_roles'] = ['membership_owner', 'membership_manager'];

    // Prevent owner role assignment from roster UI
    $config['permissions']['prevent_owner_assignment'] = true;

    // Only Org Editor can be assigned from Add/Edit permissions UIs
    $config['member_addition_form']['fields']['permissions']['allowed_roles'] = ['org_editor'];
    $config['member_addition_form']['fields']['permissions']['excluded_roles'] = ['membership_manager', 'membership_owner'];
    $config['edit_permissions_modal']['allowed_roles'] = ['org_editor'];
    $config['edit_permissions_modal']['excluded_roles'] = ['membership_manager', 'membership_owner'];

    // Add form fields for required input + optional Org Editor only
    $config['member_addition_form']['fields']['first_name']['enabled'] = true;
    $config['member_addition_form']['fields']['first_name']['required'] = true;
    $config['member_addition_form']['fields']['last_name']['enabled'] = true;
    $config['member_addition_form']['fields']['last_name']['required'] = true;
    $config['member_addition_form']['fields']['email']['enabled'] = true;
    $config['member_addition_form']['fields']['email']['required'] = true;
    $config['member_addition_form']['fields']['description']['enabled'] = false;
    $config['member_addition_form']['fields']['permissions']['enabled'] = true;
    $config['member_addition_form']['fields']['permissions']['required'] = false;
    $config['member_addition_form']['fields']['relationship_type']['enabled'] = false;

    // Restrict edit-permissions updates to active members only
    $config['member_edit']['require_active_membership_for_role_updates'] = true;

    // Relationship policy: include only Company Admin + Regular Member, exclude Affiliate.
    // Only active relationships are shown on member cards.
    $config['relationships']['default_type'] = 'regular_member';
    $config['relationships']['allowed_relationship_types'] = ['company_admin', 'regular_member'];
    $config['relationships']['exclude_relationship_types'] = ['affiliate'];
    $config['relationships']['member_card_active_only'] = true;
    $config['relationships']['organization_list_include_active_connections'] = true;
    $config['relationships']['organization_list_active_connection_types'] = ['company_admin'];
    $config['relationships']['management_access_via_active_connections'] = true;
    $config['relationships']['management_access_active_connection_types'] = ['company_admin'];
    $config['ui']['hide_relationship_type'] = false;

    // Unified member views + remove policy + seat limit behavior
    $config['ui']['member_list']['use_unified'] = true;
    $config['ui']['member_view']['use_unified'] = true;
    $config['ui']['member_list']['show_remove_button'] = false;
    $config['ui']['member_list']['show_bulk_upload'] = false;
    $config['ui']['member_list']['display_roles_allowlist'] = ['membership_owner', 'membership_manager', 'org_editor', 'member'];
    $config['ui']['member_list']['display_roles_exclude'] = ['supplemental_member', 'cchlmembercommunity', 'cchl_member_community'];
    $config['ui']['member_list']['account_status']['enabled'] = true;
    $config['ui']['member_list']['account_status']['show_unconfirmed_label'] = true;
    $config['ui']['member_list']['account_status']['confirmed_tooltip'] = __('Account confirmed', 'wicket-acc');
    $config['ui']['member_list']['account_status']['unconfirmed_tooltip'] = __('Has not confirmed their account', 'wicket-acc');
    $config['ui']['member_list']['account_status']['unconfirmed_label'] = __('Has not confirmed their account', 'wicket-acc');
    $config['ui']['member_list']['seat_limit_message'] = __('You have reached the maximum number of assignable people under this membership.', 'wicket-acc');
    $config['seat_policy']['tier_max_assignments'] = [
        'MAS Sustaining' => 3,
        'Sustaining' => 3,
        'Joint Sustaining' => 6,
    ];
    $config['ui']['member_list']['remove_policy_callout'] = [
        'enabled' => true,
        'placement' => 'above_members',
        'title' => __('Remove Members', 'wicket-acc'),
        'message' => __('To remove a member from your organization, please contact MSA directly.', 'wicket-acc'),
        'email' => 'associationmanagement@microscopy.org',
    ];

    // Friendly labels for relationship display
    $config['relationship_types']['custom_types']['company_admin'] = __('Company Admin', 'wicket-acc');
    $config['relationship_types']['custom_types']['regular_member'] = __('Regular Member', 'wicket-acc');
    $config['relationship_types']['custom_types']['affiliate'] = __('Affiliate', 'wicket-acc');

    // Notification email content overrides (site-specific).
    $config['notifications']['confirmation_email_from'] = 'associationmanagement@microscopy.org';
    $config['notifications']['email_templates']['person_to_org_assignment']['subject'] = 'Welcome to Microscopy Society of America';
    $config['notifications']['email_templates']['person_to_org_assignment']['body'] = "Hi {{ customer.first_name }},<br><br>
You have been assigned a membership as part of {{ organization.name }}.<br><br>
You will receive an account confirmation email from {{ notification.confirmation_email_from }}, this will allow you to set your password and login for the first time.<br><br>
Going forward you can visit Microscopy Society of America and login to complete your profile and access your resources.<br><br>
Thank you,<br>
Microscopy Society of America";
    $config['notifications']['email_templates']['group_assignment']['subject'] = 'Welcome to Microscopy Society of America';
    $config['notifications']['email_templates']['group_assignment']['body'] = "Hi {{ customer.first_name }},<br><br>
You have been assigned a membership as part of {{ organization.name }}.<br><br>
You will receive an account confirmation email from {{ notification.confirmation_email_from }}, this will allow you to set your password and login for the first time.<br><br>
Going forward you can visit Microscopy Society of America and login to complete your profile and access your resources.<br><br>
Thank you,<br>
Microscopy Society of America";

    return $config;
}
```
