# NJBIA Configuration

Source of truth: `../njbia-website-wordpress/src/wp-content/themes/njbia/theme/inc/org-roster.php`

## Active Strategy

- `membership.strategy = cascade`

## Canonical Overrides

### `access`

- `access.permissions.add_member_roles = ['membership_manager']`
- `access.permissions.remove_member_roles = ['membership_manager']`
- `access.permissions.manage_member_roles = ['membership_manager']`
- `access.permissions.prevent_owner_removal = true`
- `access.permissions.relationship_grants.enabled = true`
- `access.permissions.relationship_grants.roles_by_type.ceo = ['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.primary_hr_contact = ['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.member_contact = ['org_editor', 'membership_manager']`

### `membership`

- `membership.strategy = cascade`

### `relationships`

- `relationships.defaults.type = member_contact`
- `relationships.filters.allowlist = []`
- `relationships.filters.denylist = []`
- `relationships.labels.custom.employee_staff = Employee`

### `member_management`

- `member_management.forms.add_member.layout = simplified`
- `member_management.forms.add_member.fields.first_name.enabled = true`
- `member_management.forms.add_member.fields.last_name.enabled = true`
- `member_management.forms.add_member.fields.email.enabled = true`
- `member_management.forms.add_member.fields.relationship_type.enabled = true`
- `member_management.forms.add_member.fields.description.enabled = true`
- `member_management.forms.add_member.fields.description.label = Job Title`
- `member_management.forms.add_member.fields.description.input_type = text`
- `member_management.forms.add_member.allow_relationship_type_editing = true`
- `member_management.bulk_upload.columns.first_name.enabled = true`
- `member_management.bulk_upload.columns.last_name.enabled = true`
- `member_management.bulk_upload.columns.email.enabled = true`
- `member_management.bulk_upload.columns.relationship_type.enabled = true`
- `member_management.bulk_upload.columns.roles.enabled = true`
- `member_management.bulk_upload.relationship_type.required = true`
- `member_management.bulk_upload.relationship_type.allowed_types = ['employee_staff']`
- `member_management.bulk_upload.relationship_type.aliases.employee = employee_staff`

### `presentation`

- `presentation.relationships.show_type = true`
- `presentation.relationships.show_special_types = true`
- `presentation.member_list.show_bulk_upload = true`
- `presentation.member_card.fields.relationship_type.enabled = true`
- `presentation.member_card.fields.job_title.enabled = false`
- `presentation.member_card.fields.job_title.label = Job Title`
- `presentation.member_card.fields.description.enabled = true`

## Legacy To Canonical Map

- `roster.strategy -> membership.strategy`
- `relationships.default_type -> relationships.defaults.type`
- `relationships.allowed_relationship_types -> relationships.filters.allowlist`
- `relationships.exclude_relationship_types -> relationships.filters.denylist`
- `relationship_types.custom_types.* -> relationships.labels.custom.*`
- `member_addition_form.* -> member_management.forms.add_member.*`
- `bulk_upload.* -> member_management.bulk_upload.*`
- `permissions.add_members -> access.permissions.add_member_roles`
- `permissions.remove_members -> access.permissions.remove_member_roles`
- `permissions.manage_members -> access.permissions.manage_member_roles`
- `permissions.prevent_owner_removal -> access.permissions.prevent_owner_removal`
- `permissions.relationship_based_permissions -> access.permissions.relationship_grants.enabled`
- `permissions.relationship_roles_map.* -> access.permissions.relationship_grants.roles_by_type.*`
- `ui.show_special_relationships -> presentation.relationships.show_special_types`
- `ui.hide_relationship_type -> presentation.relationships.show_type`
  - Invert the value.
- `ui.member_card_fields.* -> presentation.member_card.fields.*`
- `ui.member_list.show_bulk_upload -> presentation.member_list.show_bulk_upload`

## Copy/Paste Config Function

```php
function njbia_orgman_config(array $config): array
{
    $config['membership']['strategy'] = 'cascade';

    $config['relationships']['defaults']['type'] = 'member_contact';
    $config['relationships']['filters']['allowlist'] = [];
    $config['relationships']['filters']['denylist'] = [];
    $config['relationships']['labels']['custom']['employee_staff'] = __('Employee', 'wicket-acc');

    $config['member_management']['forms']['add_member']['layout'] = 'simplified';
    $config['member_management']['forms']['add_member']['fields']['first_name']['enabled'] = true;
    $config['member_management']['forms']['add_member']['fields']['last_name']['enabled'] = true;
    $config['member_management']['forms']['add_member']['fields']['email']['enabled'] = true;
    $config['member_management']['forms']['add_member']['fields']['relationship_type']['enabled'] = true;
    $config['member_management']['forms']['add_member']['fields']['description']['enabled'] = true;
    $config['member_management']['forms']['add_member']['fields']['description']['label'] = __('Job Title', 'wicket-acc');
    $config['member_management']['forms']['add_member']['fields']['description']['input_type'] = 'text';
    $config['member_management']['forms']['add_member']['allow_relationship_type_editing'] = true;

    $config['access']['permissions']['add_member_roles'] = ['membership_manager'];
    $config['access']['permissions']['remove_member_roles'] = ['membership_manager'];
    $config['access']['permissions']['manage_member_roles'] = ['membership_manager'];
    $config['access']['permissions']['prevent_owner_removal'] = true;
    $config['access']['permissions']['relationship_grants']['enabled'] = true;
    $config['access']['permissions']['relationship_grants']['roles_by_type'] = [
        'ceo' => ['org_editor', 'membership_manager'],
        'primary_hr_contact' => ['org_editor', 'membership_manager'],
        'member_contact' => ['org_editor', 'membership_manager'],
        'employee' => [],
        'advertising_sponsor_contact' => [],
        'advertising_sponsor_billing' => [],
    ];

    $config['presentation']['relationships']['show_special_types'] = true;
    $config['presentation']['relationships']['show_type'] = true;
    $config['presentation']['member_list']['show_bulk_upload'] = true;
    $config['presentation']['member_card']['fields']['relationship_type']['enabled'] = true;
    $config['presentation']['member_card']['fields']['job_title']['enabled'] = false;
    $config['presentation']['member_card']['fields']['job_title']['label'] = __('Job Title', 'wicket-acc');
    $config['presentation']['member_card']['fields']['description']['enabled'] = true;

    $config['member_management']['bulk_upload']['columns'] = [
        'first_name' => [
            'enabled' => true,
            'required' => true,
            'header' => __('First Name', 'wicket-acc'),
            'aliases' => ['first name', 'firstname', 'first'],
        ],
        'last_name' => [
            'enabled' => true,
            'required' => true,
            'header' => __('Last Name', 'wicket-acc'),
            'aliases' => ['last name', 'lastname', 'last'],
        ],
        'email' => [
            'enabled' => true,
            'required' => true,
            'header' => __('Email Address', 'wicket-acc'),
            'aliases' => ['email address', 'email', 'e-mail'],
        ],
        'relationship_type' => [
            'enabled' => true,
            'required' => true,
            'header' => __('Relationship Type', 'wicket-acc'),
            'aliases' => ['relationship type', 'relationship'],
        ],
        'roles' => [
            'enabled' => true,
            'required' => false,
            'header' => __('Roles', 'wicket-acc'),
            'aliases' => ['roles', 'permissions', 'role'],
        ],
    ];
    $config['member_management']['bulk_upload']['relationship_type'] = [
        'required' => true,
        'allowed_types' => ['employee_staff'],
        'aliases' => [
            'employee' => 'employee_staff',
        ],
    ];

    return $config;
}
```
