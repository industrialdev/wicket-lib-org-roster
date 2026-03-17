# MSA Configuration

Source of truth: `../msa-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`

## Active Strategy

- `membership.strategy = cascade`

## Canonical Overrides

### `access`

- `access.permissions.manage_member_roles = ['membership_manager', 'membership_owner']`
- `access.permissions.add_member_roles = ['membership_manager', 'membership_owner']`
- `access.permissions.remove_member_roles = []`
- `access.permissions.prevent_owner_assignment = true`
- `access.permissions.role_only_management_access.enabled = true`
- `access.permissions.role_only_management_access.allowed_roles = ['membership_owner', 'membership_manager']`

### `membership`

- `membership.strategy = cascade`
- `membership.resolution.prefer_current_cycle = true`
- `membership.seat_limits.tier_max_assignments.MAS Sustaining = 3`
- `membership.seat_limits.tier_max_assignments.Sustaining = 3`
- `membership.seat_limits.tier_max_assignments.Joint Sustaining = 6`

### `relationships`

- `relationships.defaults.type = regular_member`
- `relationships.filters.allowlist = ['company_admin', 'regular_member']`
- `relationships.filters.denylist = ['affiliate']`
- `relationships.display.member_card_active_only = true`
- `relationships.labels.custom.company_admin = Company Admin`
- `relationships.labels.custom.regular_member = Regular Member`
- `relationships.labels.custom.affiliate = Affiliate`

### `member_management`

- `member_management.addition.auto_assign_roles = []`
- `member_management.forms.add_member.fields.first_name.enabled = true`
- `member_management.forms.add_member.fields.first_name.required = true`
- `member_management.forms.add_member.fields.last_name.enabled = true`
- `member_management.forms.add_member.fields.last_name.required = true`
- `member_management.forms.add_member.fields.email.enabled = true`
- `member_management.forms.add_member.fields.email.required = true`
- `member_management.forms.add_member.fields.description.enabled = false`
- `member_management.forms.add_member.fields.permissions.enabled = true`
- `member_management.forms.add_member.fields.permissions.required = false`
- `member_management.forms.add_member.fields.permissions.allowlist = ['org_editor']`
- `member_management.forms.add_member.fields.permissions.denylist = ['membership_manager', 'membership_owner']`
- `member_management.forms.add_member.fields.relationship_type.enabled = false`
- `member_management.permissions_modal.allowlist = ['org_editor']`
- `member_management.permissions_modal.denylist = ['membership_manager', 'membership_owner']`
- `member_management.edit.require_active_membership_for_role_updates = true`

### `presentation`

- `presentation.relationships.show_type = true`
- `presentation.member_list.use_unified = true`
- `presentation.member_list.show_bulk_upload = false`
- `presentation.member_list.show_remove_button = false`
- `presentation.member_list.display_roles.allowlist = ['membership_owner', 'membership_manager', 'org_editor', 'member']`
- `presentation.member_list.display_roles.denylist = ['supplemental_member', 'cchlmembercommunity', 'cchl_member_community']`
- `presentation.member_list.account_status.enabled = true`
- `presentation.member_list.account_status.show_unconfirmed_label = true`
- `presentation.member_list.account_status.confirmed_tooltip = Account confirmed`
- `presentation.member_list.account_status.unconfirmed_tooltip = Has not confirmed their account`
- `presentation.member_list.account_status.unconfirmed_label = Has not confirmed their account`
- `presentation.member_list.seat_limit_message = You have reached the maximum number of assignable people under this membership.`
- `presentation.member_list.remove_policy_callout.enabled = true`
- `presentation.member_list.remove_policy_callout.placement = above_members`
- `presentation.member_list.remove_policy_callout.title = Remove Members`
- `presentation.member_list.remove_policy_callout.message = To remove a member from your organization, please contact MSA directly.`
- `presentation.member_list.remove_policy_callout.email = associationmanagement@microscopy.org`
- `presentation.member_view.use_unified = true`

### `integrations`

- `integrations.notifications.confirmation_email_from = associationmanagement@microscopy.org`

## Site Extensions To Carry Forward

These appeared in the site config but are not part of the current library canonical schema yet.

- `relationships.organization_list_include_active_connections = true`
- `relationships.organization_list_active_connection_types = ['company_admin']`
- `relationships.management_access_via_active_connections = true`
- `relationships.management_access_active_connection_types = ['company_admin']`
- `integrations.notifications.email_templates.person_to_org_assignment.*`
- `integrations.notifications.email_templates.group_assignment.*`

Keep these as site-level extensions until the library grows a canonical home for them.

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `feature_flags.membership_resolution_prefer_current_cycle -> membership.resolution.prefer_current_cycle`
- `permissions.manage_members -> access.permissions.manage_member_roles`
- `permissions.add_members -> access.permissions.add_member_roles`
- `permissions.remove_members -> access.permissions.remove_member_roles`
- `permissions.prevent_owner_assignment -> access.permissions.prevent_owner_assignment`
- `permissions.role_only_management_access.* -> access.permissions.role_only_management_access.*`
- `member_addition.auto_assign_roles -> member_management.addition.auto_assign_roles`
- `member_addition_form.* -> member_management.forms.add_member.*`
- `edit_permissions_modal.* -> member_management.permissions_modal.*`
- `member_edit.require_active_membership_for_role_updates -> member_management.edit.require_active_membership_for_role_updates`
- `relationships.default_type -> relationships.defaults.type`
- `relationships.allowed_relationship_types -> relationships.filters.allowlist`
- `relationships.exclude_relationship_types -> relationships.filters.denylist`
- `relationships.member_card_active_only -> relationships.display.member_card_active_only`
- `relationship_types.custom_types.* -> relationships.labels.custom.*`
- `seat_policy.tier_max_assignments -> membership.seat_limits.tier_max_assignments`
- `ui.hide_relationship_type -> presentation.relationships.show_type`
  - Invert the value.
- `ui.member_list.* -> presentation.member_list.*`
- `ui.member_view.use_unified -> presentation.member_view.use_unified`
- `notifications.confirmation_email_from -> integrations.notifications.confirmation_email_from`

## Copy/Paste Config Function

```php
function wicket_child_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'cascade';
    $config['membership']['resolution']['prefer_current_cycle'] = true;
    $config['membership']['seat_limits']['tier_max_assignments'] = [
        'MAS Sustaining' => 3,
        'Sustaining' => 3,
        'Joint Sustaining' => 6,
    ];

    $config['access']['permissions']['manage_member_roles'] = ['membership_manager', 'membership_owner'];
    $config['access']['permissions']['add_member_roles'] = ['membership_manager', 'membership_owner'];
    $config['access']['permissions']['remove_member_roles'] = [];
    $config['access']['permissions']['role_only_management_access']['enabled'] = true;
    $config['access']['permissions']['role_only_management_access']['allowed_roles'] = ['membership_owner', 'membership_manager'];
    $config['access']['permissions']['prevent_owner_assignment'] = true;

    $config['member_management']['addition']['auto_assign_roles'] = [];
    $config['member_management']['forms']['add_member']['fields']['permissions']['allowlist'] = ['org_editor'];
    $config['member_management']['forms']['add_member']['fields']['permissions']['denylist'] = ['membership_manager', 'membership_owner'];
    $config['member_management']['forms']['add_member']['fields']['description']['enabled'] = false;
    $config['member_management']['forms']['add_member']['fields']['permissions']['required'] = false;
    $config['member_management']['forms']['add_member']['fields']['relationship_type']['enabled'] = false;
    $config['member_management']['permissions_modal']['allowlist'] = ['org_editor'];
    $config['member_management']['permissions_modal']['denylist'] = ['membership_manager', 'membership_owner'];
    $config['member_management']['edit']['require_active_membership_for_role_updates'] = true;

    $config['relationships']['defaults']['type'] = 'regular_member';
    $config['relationships']['filters']['allowlist'] = ['company_admin', 'regular_member'];
    $config['relationships']['filters']['denylist'] = ['affiliate'];
    $config['relationships']['display']['member_card_active_only'] = true;
    $config['relationships']['labels']['custom']['company_admin'] = __('Company Admin', 'wicket-acc');
    $config['relationships']['labels']['custom']['regular_member'] = __('Regular Member', 'wicket-acc');
    $config['relationships']['labels']['custom']['affiliate'] = __('Affiliate', 'wicket-acc');

    $config['presentation']['relationships']['show_type'] = true;
    $config['presentation']['member_list']['use_unified'] = true;
    $config['presentation']['member_view']['use_unified'] = true;
    $config['presentation']['member_list']['show_remove_button'] = false;
    $config['presentation']['member_list']['show_bulk_upload'] = false;
    $config['presentation']['member_list']['display_roles']['allowlist'] = ['membership_owner', 'membership_manager', 'org_editor', 'member'];
    $config['presentation']['member_list']['display_roles']['denylist'] = ['supplemental_member', 'cchlmembercommunity', 'cchl_member_community'];
    $config['presentation']['member_list']['account_status']['enabled'] = true;
    $config['presentation']['member_list']['account_status']['show_unconfirmed_label'] = true;
    $config['presentation']['member_list']['account_status']['confirmed_tooltip'] = __('Account confirmed', 'wicket-acc');
    $config['presentation']['member_list']['account_status']['unconfirmed_tooltip'] = __('Has not confirmed their account', 'wicket-acc');
    $config['presentation']['member_list']['account_status']['unconfirmed_label'] = __('Has not confirmed their account', 'wicket-acc');
    $config['presentation']['member_list']['seat_limit_message'] = __('You have reached the maximum number of assignable people under this membership.', 'wicket-acc');
    $config['presentation']['member_list']['remove_policy_callout'] = [
        'enabled' => true,
        'placement' => 'above_members',
        'title' => __('Remove Members', 'wicket-acc'),
        'message' => __('To remove a member from your organization, please contact MSA directly.', 'wicket-acc'),
        'email' => 'associationmanagement@microscopy.org',
    ];

    $config['integrations']['notifications']['confirmation_email_from'] = 'associationmanagement@microscopy.org';

    // Site extensions not yet standardized in the library.
    $config['relationships']['organization_list_include_active_connections'] = true;
    $config['relationships']['organization_list_active_connection_types'] = ['company_admin'];
    $config['relationships']['management_access_via_active_connections'] = true;
    $config['relationships']['management_access_active_connection_types'] = ['company_admin'];
    $config['integrations']['notifications']['email_templates']['person_to_org_assignment']['subject'] = 'Welcome to Microscopy Society of America';
    $config['integrations']['notifications']['email_templates']['group_assignment']['subject'] = 'Welcome to Microscopy Society of America';

    return $config;
}
```
