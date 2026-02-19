# MSA Roster Configuration Baseline

Date: 2026-02-18

This document stores the finalized baseline configuration for the MSA roster requirement (cascading membership style).

Library config source: `\OrgManagement\Config\get_config()` in `src/config/config.php`.
Do not edit `src/config/config.php` directly on client sites. Configure through the centralized filter: `wicket/acc/orgman/config`.

## Baseline Config (Copy/Paste)

```php
add_filter('wicket/acc/orgman/config', function (array $config): array {
    // Strategy
    $config['roster']['strategy'] = 'cascade';

    // Management permissions
    $config['permissions']['manage_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = []; // disables remove endpoint authorization

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
    $config['ui']['hide_relationship_type'] = false;

    // Unified member views + remove policy + seat limit behavior
    $config['ui']['member_list']['use_unified'] = true;
    $config['ui']['member_view']['use_unified'] = true;
    $config['ui']['member_list']['show_remove_button'] = false;
    $config['ui']['member_list']['show_bulk_upload'] = false;
    $config['ui']['member_list']['seat_limit_message'] = __('You have reached the maximum number of assignable people under this membership.', 'wicket-acc');
    $config['seat_policy']['tier_max_assignments'] = [
        'MAS Sustaining' => 3,
        'Sustaining' => 3,
        'Joint Sustaining' => 6,
    ];
    $config['ui']['member_list']['remove_policy_callout'] = [
        'enabled' => true,
        'placement' => 'above_members', // or 'below_members'
        'title' => __('Remove Members', 'wicket-acc'),
        'message' => __('To Remove a member from your organization, please contact MSA directly.', 'wicket-acc'),
        'email' => 'associationmanagement@microscopy.org',
    ];

    // Friendly labels for relationship display
    $config['relationship_types']['custom_types']['company_admin'] = __('Company Admin', 'wicket-acc');
    $config['relationship_types']['custom_types']['regular_member'] = __('Regular Member', 'wicket-acc');
    $config['relationship_types']['custom_types']['affiliate'] = __('Affiliate', 'wicket-acc');

    return $config;
});
```

## Requirement Coverage

- [x] Membership style is Cascading (`roster.strategy = cascade`).
- [x] Membership Manager and Membership Owner can manage and add roster members.
- [x] Organization summary supports Organization Name, Membership Tier, Membership Owner, Renewal Date, and Seats assigned (# / max).
- [x] Edit Team Roster can only assign/remove `org_editor` and blocks `membership_manager` assignment.
- [x] Edit Team Roster role updates are blocked for inactive members (`member_edit.require_active_membership_for_role_updates = true`).
- [x] Manage Team Members page supports keyword search.
- [x] Team member cards show active relationships only and support comma-separated multi-relationship display.
- [x] Relationship filtering includes `company_admin` and `regular_member`, excludes `affiliate`.
- [x] Remove-from-roster actions are hidden and MSA callout is displayed with email contact.
- [x] Add Member required fields are First Name, Last Name, and Email.
- [x] Optional additional role is limited to `org_editor`.
- [x] Seat limit hides Add Member and shows required seat-limit message copy.
- [x] Tier-to-seat mapping is configured:
- [x] `MAS Sustaining` => `3`
- [x] `Sustaining` => `3`
- [x] `Joint Sustaining` => `6`
