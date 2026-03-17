# NJBIA Configuration

Source of truth: `../njbia-website-wordpress/src/wp-content/themes/njbia/theme/inc/org-roster.php`

## Active Strategy
- `roster.strategy = cascade`

## Key Overrides
- Relationships:
  - `default_type = member_contact`
  - `allowed_relationship_types = []`
  - `exclude_relationship_types = []`
  - Custom labels: `employee_staff => Employee`
- Member addition form:
  - `layout = simplified`
  - Enables `first_name`, `last_name`, `email`, `relationship_type`, `description`
  - `description` labeled as `Job Title`
  - `description.input_type = text`
  - `allow_relationship_type_editing = true`
- Permissions:
  - Restricts add/remove/manage to `membership_manager`
  - `prevent_owner_removal = true`
  - `relationship_based_permissions = true`
  - Explicit `relationship_roles_map` is configured
- UI:
  - Shows special relationships
  - Shows relationship type on member cards
  - Uses description field on cards
  - Hides `job_title` field on cards
  - Sets `job_title` label to `Job Title`
  - `hide_relationship_type = false`
  - `member_list.show_bulk_upload = true`
- Bulk upload:
  - Explicit column map for `first_name`, `last_name`, `email`, `relationship_type`, `roles`
  - `relationship_type.required = true`
  - `relationship_type.allowed_types = ['employee_staff']`
  - Alias mapping: `employee => employee_staff`

## Copy/Paste Config Function
```php
function njbia_orgman_config(array $config): array
{
    $config['roster']['strategy'] = 'cascade';
    $config['relationships']['default_type'] = 'member_contact';
    $config['relationships']['allowed_relationship_types'] = [];
    $config['relationships']['exclude_relationship_types'] = [];
    $config['relationship_types']['custom_types']['employee_staff'] = __('Employee', 'wicket-acc');

    // Enable simplified member addition form with custom fields
    $config['member_addition_form']['layout'] = 'simplified';
    $config['member_addition_form']['fields']['first_name']['enabled'] = true;
    $config['member_addition_form']['fields']['last_name']['enabled'] = true;
    $config['member_addition_form']['fields']['email']['enabled'] = true;
    $config['member_addition_form']['fields']['relationship_type']['enabled'] = true;
    $config['member_addition_form']['fields']['description']['enabled'] = true;
    $config['member_addition_form']['fields']['description']['label'] = __('Job Title', 'wicket-acc');
    $config['member_addition_form']['fields']['description']['input_type'] = 'text';

    // Restrict member management to Membership Managers only (not owners)
    $config['permissions']['add_members'] = ['membership_manager'];
    $config['permissions']['prevent_owner_removal'] = true;
    $config['permissions']['remove_members'] = ['membership_manager'];
    $config['permissions']['manage_members'] = ['membership_manager'];

    // Show special relationship types on member cards
    $config['ui']['show_special_relationships'] = true;
    $config['ui']['member_card_fields']['relationship_type']['enabled'] = true;
    $config['ui']['member_card_fields']['job_title']['enabled'] = false;
    $config['ui']['member_card_fields']['job_title']['label'] = __('Job Title', 'wicket-acc');
    $config['ui']['member_card_fields']['description']['enabled'] = true;
    $config['ui']['hide_relationship_type'] = false;
    $config['ui']['member_list']['show_bulk_upload'] = true;

    // Bulk upload: keep all imported columns enabled for this site.
    $config['bulk_upload']['columns'] = [
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
    $config['bulk_upload']['relationship_type'] = [
        'required' => true,
        'allowed_types' => ['employee_staff'],
        'aliases' => [
            'employee' => 'employee_staff',
        ],
    ];

    // Enable relationship-based permissions
    $config['permissions']['relationship_based_permissions'] = true;
    $config['permissions']['relationship_roles_map'] = [
        'ceo' => ['org_editor', 'membership_manager'],
        'primary_hr_contact' => ['org_editor', 'membership_manager'],
        'member_contact' => ['org_editor', 'membership_manager'],
        'employee' => [],
        'advertising_sponsor_contact' => [],
        'advertising_sponsor_billing' => [],
    ];

    // Enable relationship type editing
    $config['member_addition_form']['allow_relationship_type_editing'] = true;

    return $config;
}
```
