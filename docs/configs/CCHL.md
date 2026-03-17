# CCHL Configuration

Source of truth: `../cchl-website-wordpress/src/web/app/themes/industrial/custom/org-roster.php`

## Active Strategy

- `membership.strategy = direct`

## Canonical Overrides

### `access`

- `access.roles.owner = membership_owner`
- `access.roles.manager = membership_manager`
- `access.roles.editor = org_editor`
- `access.roles.labels.membership_manager = Membership Manager`
- `access.roles.labels.org_editor = Org Editor`
- `access.roles.labels.membership_owner = Membership Owner`
- `access.roles.labels.Cchlmembercommunity = CCHL Member Community`
- `access.roles.labels.cchlmembercommunity = CCHL Member Community`
- `access.permissions.organization_edit_roles = ['org_editor']`
- `access.permissions.manage_member_roles = ['membership_manager', 'membership_owner']`
- `access.permissions.add_member_roles = ['membership_manager', 'membership_owner']`
- `access.permissions.remove_member_roles = ['membership_manager', 'membership_owner']`
- `access.permissions.purchase_seat_roles = ['membership_owner', 'membership_manager', 'org_editor']`
- `access.permissions.any_management_roles = ['org_editor', 'membership_manager', 'membership_owner']`
- `access.permissions.prevent_owner_removal = false`
- `access.permissions.prevent_owner_assignment = true`
- `access.permissions.relationship_grants.enabled = false`
- `access.permissions.relationship_grants.roles_by_type.ceo = ['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.primary_hr_contact = ['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.member_contact = ['org_editor', 'membership_manager']`

### `membership`

- `membership.strategy = direct`

### `relationships`

- `relationships.defaults.type = Position`
- `relationships.addition.type = position`
- `relationships.filters.allowlist = []`
- `relationships.filters.denylist = []`
- `relationships.labels.custom.ceo = CEO`
- `relationships.labels.custom.primary_hr_contact = Primary HR Contact`
- `relationships.labels.custom.employee = Employee`
- `relationships.labels.custom.member_contact = Member Contact`
- `relationships.labels.special.advertising_sponsor_contact = Advertising/Sponsor Contact`
- `relationships.labels.special.advertising_sponsor_billing = Advertising/Sponsor Billing Contact`

### `member_management`

- `member_management.addition.auto_assign_roles = ['supplemental_member', 'CCHL Member Community']`
- `member_management.addition.base_member_role = member`
- `member_management.addition.auto_opt_in_communications.enabled = true`
- `member_management.addition.auto_opt_in_communications.email = true`
- `member_management.addition.auto_opt_in_communications.sublists = ['one', 'two', 'three', 'four', 'five']`
- `member_management.forms.add_member.layout = full`
- `member_management.forms.add_member.fields.first_name.enabled = true`
- `member_management.forms.add_member.fields.first_name.required = true`
- `member_management.forms.add_member.fields.last_name.enabled = true`
- `member_management.forms.add_member.fields.last_name.required = true`
- `member_management.forms.add_member.fields.email.enabled = true`
- `member_management.forms.add_member.fields.email.required = true`
- `member_management.forms.add_member.fields.relationship_type.enabled = false`
- `member_management.forms.add_member.fields.relationship_type.required = false`
- `member_management.forms.add_member.fields.permissions.enabled = true`
- `member_management.forms.add_member.fields.permissions.required = true`
- `member_management.forms.add_member.fields.permissions.denylist = ['Cchlmembercommunity', 'cchlmembercommunity']`
- `member_management.forms.add_member.allow_relationship_type_editing = false`
- `member_management.permissions_modal.allowlist = []`
- `member_management.permissions_modal.denylist = ['Cchlmembercommunity', 'cchlmembercommunity']`

### `presentation`

- `presentation.relationships.show_type = false`
- `presentation.relationships.show_special_types = false`
- `presentation.member_card.fields.name.enabled = true`
- `presentation.member_card.fields.email.enabled = true`
- `presentation.member_card.fields.roles.enabled = true`
- `presentation.member_card.fields.relationship_type.enabled = false`

### `integrations`

- `integrations.additional_seats.enabled = true`
- `integrations.additional_seats.sku = additional-seats`
- `integrations.additional_seats.form_id = 0`
- `integrations.additional_seats.form_slug = additional-seats`
- `integrations.additional_seats.min_quantity = 1`
- `integrations.additional_seats.max_quantity = 900`
- `integrations.documents.allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']`
- `integrations.documents.max_size = 10485760`
- `integrations.business_info.seat_limit_info = null`
- `integrations.notifications.confirmation_email_from = cchl@wicketcloud.com`

### `platform`

- `platform.cache.enabled = false`
- `platform.cache.duration = 300`

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `roles.* -> access.roles.*`
- `role_labels.* -> access.roles.labels.*`
- `permissions.* -> access.permissions.*`
- `member_addition.* -> member_management.addition.*`
- `member_addition_form.* -> member_management.forms.add_member.*`
- `edit_permissions_modal.* -> member_management.permissions_modal.*`
- `relationships.default_type -> relationships.defaults.type`
- `relationships.member_addition_type -> relationships.addition.type`
- `relationships.allowed_relationship_types -> relationships.filters.allowlist`
- `relationships.exclude_relationship_types -> relationships.filters.denylist`
- `relationship_types.custom_types.* -> relationships.labels.custom.*`
- `relationship_types.special_types.* -> relationships.labels.special.*`
- `ui.hide_relationship_type -> presentation.relationships.show_type`
  - Invert the value.
- `ui.show_special_relationships -> presentation.relationships.show_special_types`
- `ui.member_card_fields.* -> presentation.member_card.fields.*`
- `additional_seats.* -> integrations.additional_seats.*`
- `documents.* -> integrations.documents.*`
- `business_info.* -> integrations.business_info.*`
- `notifications.* -> integrations.notifications.*`
- `cache.* -> platform.cache.*`

## Copy/Paste Config Function

```php
function wicket_orgman_config(array $config): array
{
    $config['access']['roles'] = [
        'owner' => 'membership_owner',
        'manager' => 'membership_manager',
        'editor' => 'org_editor',
        'labels' => [
            'membership_manager' => 'Membership Manager',
            'org_editor' => 'Org Editor',
            'membership_owner' => 'Membership Owner',
            'Cchlmembercommunity' => 'CCHL Member Community',
            'cchlmembercommunity' => 'CCHL Member Community',
        ],
    ];

    $config['access']['permissions']['organization_edit_roles'] = ['org_editor'];
    $config['access']['permissions']['manage_member_roles'] = ['membership_manager', 'membership_owner'];
    $config['access']['permissions']['add_member_roles'] = ['membership_manager', 'membership_owner'];
    $config['access']['permissions']['remove_member_roles'] = ['membership_manager', 'membership_owner'];
    $config['access']['permissions']['purchase_seat_roles'] = ['membership_owner', 'membership_manager', 'org_editor'];
    $config['access']['permissions']['any_management_roles'] = ['org_editor', 'membership_manager', 'membership_owner'];
    $config['access']['permissions']['prevent_owner_removal'] = false;
    $config['access']['permissions']['prevent_owner_assignment'] = true;
    $config['access']['permissions']['relationship_grants']['enabled'] = false;
    $config['access']['permissions']['relationship_grants']['roles_by_type'] = [
        'ceo' => ['org_editor', 'membership_manager'],
        'primary_hr_contact' => ['org_editor', 'membership_manager'],
        'member_contact' => ['org_editor', 'membership_manager'],
        'employee' => [],
        'advertising_sponsor_contact' => [],
        'advertising_sponsor_billing' => [],
    ];

    $config['membership']['strategy'] = 'direct';

    $config['relationships']['defaults']['type'] = 'Position';
    $config['relationships']['addition']['type'] = 'position';
    $config['relationships']['filters']['allowlist'] = [];
    $config['relationships']['filters']['denylist'] = [];
    $config['relationships']['labels']['custom'] = [
        'ceo' => 'CEO',
        'primary_hr_contact' => 'Primary HR Contact',
        'employee' => 'Employee',
        'member_contact' => 'Member Contact',
    ];
    $config['relationships']['labels']['special'] = [
        'advertising_sponsor_contact' => 'Advertising/Sponsor Contact',
        'advertising_sponsor_billing' => 'Advertising/Sponsor Billing Contact',
    ];

    $config['member_management']['addition']['auto_assign_roles'] = [
        'supplemental_member',
        'CCHL Member Community',
    ];
    $config['member_management']['addition']['base_member_role'] = 'member';
    $config['member_management']['addition']['auto_opt_in_communications'] = [
        'enabled' => true,
        'email' => true,
        'sublists' => ['one', 'two', 'three', 'four', 'five'],
    ];
    $config['member_management']['forms']['add_member']['layout'] = 'full';
    $config['member_management']['forms']['add_member']['fields']['relationship_type']['enabled'] = false;
    $config['member_management']['forms']['add_member']['fields']['permissions']['denylist'] = ['Cchlmembercommunity', 'cchlmembercommunity'];
    $config['member_management']['forms']['add_member']['allow_relationship_type_editing'] = false;
    $config['member_management']['permissions_modal']['allowlist'] = [];
    $config['member_management']['permissions_modal']['denylist'] = ['Cchlmembercommunity', 'cchlmembercommunity'];

    $config['presentation']['relationships']['show_type'] = false;
    $config['presentation']['relationships']['show_special_types'] = false;
    $config['presentation']['member_card']['fields']['name']['enabled'] = true;
    $config['presentation']['member_card']['fields']['email']['enabled'] = true;
    $config['presentation']['member_card']['fields']['roles']['enabled'] = true;
    $config['presentation']['member_card']['fields']['relationship_type']['enabled'] = false;

    $config['integrations']['additional_seats']['enabled'] = true;
    $config['integrations']['additional_seats']['sku'] = 'additional-seats';
    $config['integrations']['additional_seats']['form_id'] = 0;
    $config['integrations']['additional_seats']['form_slug'] = 'additional-seats';
    $config['integrations']['additional_seats']['min_quantity'] = 1;
    $config['integrations']['additional_seats']['max_quantity'] = 900;
    $config['integrations']['documents']['allowed_types'] = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif',
    ];
    $config['integrations']['documents']['max_size'] = 10 * 1024 * 1024;
    $config['integrations']['business_info']['seat_limit_info'] = null;
    $config['integrations']['notifications']['confirmation_email_from'] = 'cchl@wicketcloud.com';

    $config['platform']['cache']['enabled'] = false;
    $config['platform']['cache']['duration'] = 5 * 60;

    return $config;
}
```
