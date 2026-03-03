# NJBIA Configuration

Date: 2026-03-03
Source of truth: `/Users/esteban/Dev/Projects-Wicket/njbia-website-wordpress/src/wp-content/themes/njbia/theme/inc/org-roster.php`

## Active Strategy
- `roster.strategy = cascade`

## Key Overrides
- Relationships:
  - `default_type = member_contact`
  - No allow/exclude filtering lists
  - Custom labels: `employee_staff => Employee`, `grade_4 => Grade 4`
- Member addition form:
  - `layout = simplified`
  - Enables `first_name`, `last_name`, `email`, `relationship_type`, `description`
  - `description` labeled as `Job Title`
- Permissions:
  - Restricts add/remove/manage to `membership_manager`
  - `prevent_owner_removal = true`
  - Relationship-based permissions enabled with explicit relationship-role map
- UI:
  - Shows special relationships
  - Shows relationship type on member cards
  - Uses description field on card, hides job_title field
  - Relationship type not hidden
  - Bulk upload enabled in member list
- Bulk upload:
  - Explicit column map for first name, last name, email, relationship type, roles
  - Relationship type required and constrained to `employee_staff` / `grade_4`
  - Alias mapping for relationship values
- Member addition:
  - `allow_relationship_type_editing = true`

## Copy/Paste Config Function
```php
function njbia_orgman_config(array $config): array
{
    $config['roster']['strategy'] = 'cascade';
    $config['relationships']['default_type'] = 'member_contact';
    $config['relationships']['allowed_relationship_types'] = [];
    $config['relationships']['exclude_relationship_types'] = [];
    $config['relationship_types']['custom_types']['employee_staff'] = __('Employee', 'wicket-acc');
    $config['relationship_types']['custom_types']['grade_4'] = __('Grade 4', 'wicket-acc');

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
        'allowed_types' => ['employee_staff', 'grade_4'],
        'aliases' => [
            'employee' => 'employee_staff',
            'grade 4' => 'grade_4',
            'grade_4' => 'grade_4',
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
