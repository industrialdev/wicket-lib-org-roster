# CCHL Configuration

Source of truth: `../cchl-website-wordpress/src/web/app/themes/industrial/custom/org-roster.php`

## Active Strategy
- `roster.strategy = direct`

## Key Overrides
- Roles:
  - `roles.owner = membership_owner`
  - `roles.manager = membership_manager`
  - `roles.editor = org_editor`
  - Explicit `role_labels` for membership roles and CCHL community roles
- Permissions:
  - `edit_organization = ['org_editor']`
  - `manage_members`, `add_members`, `remove_members` allow `membership_manager` and `membership_owner`
  - `purchase_seats` allows `membership_owner`, `membership_manager`, `org_editor`
  - `any_management` allows `org_editor`, `membership_manager`, `membership_owner`
  - `prevent_owner_removal = false`
  - `prevent_owner_assignment = true`
  - `relationship_based_permissions = false`
  - Explicit `relationship_roles_map` is configured
- Member addition:
  - `auto_assign_roles = ['supplemental_member', 'CCHL Member Community']`
  - `base_member_role = member`
  - Auto opt-in communications enabled with five sublists
- Cache:
  - `enabled = false`
  - `duration = 300`
- Relationships:
  - `default_type = Position`
  - `member_addition_type = position`
  - `allowed_relationship_types = []`
  - `exclude_relationship_types = []`
- Additional seats:
  - Enabled with SKU `additional-seats`
  - `form_id = 0`
  - `form_slug = additional-seats`
  - Quantity range `1..900`
- Documents:
  - Allowed types: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `jpg`, `jpeg`, `png`, `gif`
  - `max_size = 10485760`
- Business info:
  - `seat_limit_info = null`
- UI:
  - `hide_relationship_type = true`
  - `show_special_relationships = false`
  - Member card fields: `name`, `email`, `roles` enabled; `relationship_type` disabled
- Member addition form:
  - `layout = full`
  - `first_name`, `last_name`, `email` enabled and required
  - `relationship_type` disabled and not required
  - `permissions` enabled and required
  - `permissions.excluded_roles = ['Cchlmembercommunity', 'cchlmembercommunity']`
  - `allow_relationship_type_editing = false`
- Edit permissions modal:
  - `allowed_roles = []`
  - `excluded_roles = ['Cchlmembercommunity', 'cchlmembercommunity']`
- Notifications:
  - `confirmation_email_from = cchl@wicketcloud.com`
- Relationship labels:
  - Custom types: `ceo`, `primary_hr_contact`, `employee`, `member_contact`
  - Special types: `advertising_sponsor_contact`, `advertising_sponsor_billing`

## Copy/Paste Config Function
```php
function wicket_orgman_config(array $config): array
{
    // Roles
    $config['roles'] = [
        'owner' => 'membership_owner',
        'manager' => 'membership_manager',
        'editor' => 'org_editor',
    ];

    // Role labels
    $config['role_labels'] = [
        'membership_manager' => 'Membership Manager',
        'org_editor' => 'Org Editor',
        'membership_owner' => 'Membership Owner',
        'Cchlmembercommunity' => 'CCHL Member Community',
        'cchlmembercommunity' => 'CCHL Member Community',
    ];

    // Permissions
    $config['permissions']['edit_organization'] = ['org_editor'];
    $config['permissions']['manage_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['add_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['remove_members'] = ['membership_manager', 'membership_owner'];
    $config['permissions']['purchase_seats'] = ['membership_owner', 'membership_manager', 'org_editor'];
    $config['permissions']['any_management'] = ['org_editor', 'membership_manager', 'membership_owner'];
    $config['permissions']['prevent_owner_removal'] = false;
    $config['permissions']['prevent_owner_assignment'] = true;
    $config['permissions']['relationship_based_permissions'] = false;
    $config['permissions']['relationship_roles_map'] = [
        'ceo' => ['org_editor', 'membership_manager'],
        'primary_hr_contact' => ['org_editor', 'membership_manager'],
        'member_contact' => ['org_editor', 'membership_manager'],
        'employee' => [],
        'advertising_sponsor_contact' => [],
        'advertising_sponsor_billing' => [],
    ];

    // Member addition
    $config['member_addition']['auto_assign_roles'] = [
        'supplemental_member',
        'CCHL Member Community',
    ];
    $config['member_addition']['base_member_role'] = 'member';
    $config['member_addition']['auto_opt_in_communications'] = [
        'enabled' => true,
        'email' => true,
        'sublists' => ['one', 'two', 'three', 'four', 'five'],
    ];

    // Cache
    $config['cache']['enabled'] = false;
    $config['cache']['duration'] = 5 * 60;

    // Relationships
    $config['relationships']['default_type'] = 'Position';
    $config['relationships']['member_addition_type'] = 'position';
    $config['relationships']['allowed_relationship_types'] = [];
    $config['relationships']['exclude_relationship_types'] = [];

    // Roster strategy
    $config['roster']['strategy'] = 'direct';

    // Additional seats
    $config['additional_seats']['enabled'] = true;
    $config['additional_seats']['sku'] = 'additional-seats';
    $config['additional_seats']['form_id'] = 0;
    $config['additional_seats']['form_slug'] = 'additional-seats';
    $config['additional_seats']['min_quantity'] = 1;
    $config['additional_seats']['max_quantity'] = 900;

    // Documents
    $config['documents']['allowed_types'] = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif',
    ];
    $config['documents']['max_size'] = 10 * 1024 * 1024;

    // Business info
    $config['business_info']['seat_limit_info'] = null;

    // UI settings
    $config['ui']['hide_relationship_type'] = true;
    $config['ui']['show_special_relationships'] = false;
    $config['ui']['member_card_fields'] = [
        'name' => ['enabled' => true, 'label' => 'Name'],
        'email' => ['enabled' => true, 'label' => 'Email'],
        'roles' => ['enabled' => true, 'label' => 'Roles'],
        'relationship_type' => ['enabled' => false, 'label' => 'Relationship'],
    ];

    // Member addition form
    $config['member_addition_form']['layout'] = 'full';
    $config['member_addition_form']['fields']['first_name'] = [
        'enabled' => true,
        'required' => true,
        'label' => __('First Name', 'wicket-acc'),
    ];
    $config['member_addition_form']['fields']['last_name'] = [
        'enabled' => true,
        'required' => true,
        'label' => __('Last Name', 'wicket-acc'),
    ];
    $config['member_addition_form']['fields']['email'] = [
        'enabled' => true,
        'required' => true,
        'label' => __('Email Address', 'wicket-acc'),
    ];
    $config['member_addition_form']['fields']['relationship_type'] = [
        'enabled' => false,
        'required' => false,
        'label' => __('Relationship Type', 'wicket-acc'),
    ];
    $config['member_addition_form']['fields']['permissions'] = [
        'enabled' => true,
        'required' => true,
        'label' => __('Permissions', 'wicket-acc'),
        'allowed_roles' => [],
        'excluded_roles' => ['Cchlmembercommunity', 'cchlmembercommunity'],
    ];
    $config['member_addition_form']['allow_relationship_type_editing'] = false;

    // Edit permissions modal
    $config['edit_permissions_modal']['allowed_roles'] = [];
    $config['edit_permissions_modal']['excluded_roles'] = ['Cchlmembercommunity', 'cchlmembercommunity'];

    // Notifications
    $config['notifications']['confirmation_email_from'] = 'cchl@wicketcloud.com';

    // Relationship types
    $config['relationship_types']['custom_types'] = [
        'ceo' => 'CEO',
        'primary_hr_contact' => 'Primary HR Contact',
        'employee' => 'Employee',
        'member_contact' => 'Member Contact',
    ];
    $config['relationship_types']['special_types'] = [
        'advertising_sponsor_contact' => 'Advertising/Sponsor Contact',
        'advertising_sponsor_billing' => 'Advertising/Sponsor Billing Contact',
    ];

    return $config;
}
```
