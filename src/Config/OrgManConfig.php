<?php

declare(strict_types=1);

namespace OrgManagement\Config;

final class OrgManConfig
{
    /**
     * Get organization roster configuration.
     *
     * @return array
     */
    public static function get(): array
    {
        if (!defined('ABSPATH')) {
            exit;
        }

        $orgmanConfig = [
            'roster' => [
                'strategy' => 'direct',
            ],
            'roles' => [
                'owner' => 'membership_owner',
                'manager' => 'membership_manager',
                'editor' => 'org_editor',
            ],
            'role_labels' => [
                'membership_manager' => __('Membership Manager', 'wicket-acc'),
                'org_editor'         => __('Org Editor', 'wicket-acc'),
                'membership_owner'   => __('Membership Owner', 'wicket-acc'),
                'Cchlmembercommunity' => __('CCHL Member Community', 'wicket-acc'),
                'cchlmembercommunity' => __('CCHL Member Community', 'wicket-acc'),
            ],
            'permissions' => [
                'edit_organization' => [
                    'org_editor',
                ],
                'manage_members' => [
                    'membership_manager',
                    'membership_owner',
                ],
                'add_members' => [
                    'membership_manager',
                    'membership_owner',
                ],
                'remove_members' => [
                    'membership_manager',
                    'membership_owner',
                ],
                'purchase_seats' => [
                    'membership_owner',
                    'membership_manager',
                    'org_editor',
                ],
                'any_management' => [
                    'org_editor',
                    'membership_manager',
                    'membership_owner',
                ],
                'prevent_owner_removal' => false,
                'relationship_based_permissions' => false,
                'relationship_roles_map' => [
                    'ceo' => [
                        'org_editor',
                        'membership_manager',
                    ],
                    'primary_hr_contact' => [
                        'org_editor',
                        'membership_manager',
                    ],
                    'member_contact' => [
                        'org_editor',
                        'membership_manager',
                    ],
                    'employee_staff' => [],
                    'advertising_sponsor_contact' => [],
                    'advertising_sponsor_billing' => [],
                ],
                'prevent_owner_assignment' => true,
                'role_only_management_access' => [
                    'enabled' => false,
                    'allowed_roles' => [
                        'membership_owner',
                    ],
                ],
            ],
            'member_addition' => [
                'auto_assign_roles' => [
                    'supplemental_member',
                    'CCHL Member Community',
                ],
                'base_member_role' => 'member',
                'auto_opt_in_communications' => [
                    'enabled' => true,
                    'email' => true,
                    'sublists' => [
                        'one',
                        'two',
                        'three',
                        'four',
                        'five',
                    ],
                ],
            ],
            'cache' => [
                'enabled' => false,
                'duration' => 5 * 60,
            ],
            'relationships' => [
                'default_type' => 'Position',
                'member_addition_type' => 'position',
                'allowed_relationship_types' => [],
                'exclude_relationship_types' => [],
                'member_card_active_only' => false,
            ],
            'groups' => [
                'tag_name' => 'Roster Management',
                'tag_case_sensitive' => false,
                'manage_roles' => [
                    'president',
                    'delegate',
                    'alternate_delegate',
                    'council_delegate',
                    'council_alternate_delegate',
                    'correspondent',
                ],
                'roster_roles' => [
                    'member',
                    'observer',
                ],
                'member_role' => 'member',
                'observer_role' => 'observer',
                'seat_limited_roles' => ['member'],
                'list' => [
                    'page_size' => 20,
                    'member_page_size' => 15,
                ],
                'additional_info' => [
                    'key' => 'association',
                    'value_field' => 'name',
                    'fallback_to_org_uuid' => true,
                ],
                'removal' => [
                    'mode' => 'end_date',
                    'end_date_format' => 'Y-m-d\\T00:00:00P',
                ],
                'ui' => [
                    'enable_group_profile_edit' => true,
                    'use_unified_member_list' => true,
                    'use_unified_member_view' => true,
                    'show_edit_permissions' => false,
                    'search_clear_requires_submit' => true,
                    'editable_fields' => [
                        'name',
                        'description',
                    ],
                ],
            ],
            'membership_cycle' => [
                'strategy_key' => 'membership_cycle',
                'permissions' => [
                    'add_roles' => [
                        'membership_manager',
                    ],
                    'remove_roles' => [
                        'membership_manager',
                    ],
                    'purchase_seats_roles' => [
                        'membership_owner',
                        'membership_manager',
                        'org_editor',
                    ],
                    'prevent_owner_removal' => true,
                ],
                'member_management' => [
                    'require_explicit_membership_uuid' => true,
                ],
            ],
            'additional_seats' => [
                'enabled' => true,
                'sku' => 'additional-seats',
                'form_id' => 0,
                'form_slug' => 'additional-seats',
                'min_quantity' => 1,
                'max_quantity' => 900,
            ],
            'documents' => [
                'allowed_types' => [
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif',
                ],
                'max_size' => 10 * 1024 * 1024,
            ],
            'business_info' => [
                'seat_limit_info' => null,
            ],
            'seat_policy' => [
                'tier_max_assignments' => [],
                'tier_name_case_sensitive' => false,
            ],
            'ui' => [
                'organization_list' => [
                    'page_size' => 5,
                ],
                'hide_relationship_type' => true,
                'show_special_relationships' => false,
                'member_list' => [
                    'use_unified' => true,
                    'show_edit_permissions' => true,
                    'show_remove_button' => true,
                    'show_bulk_upload' => false,
                    'display_roles_allowlist' => [],
                    'display_roles_exclude' => [],
                    'account_status' => [
                        'enabled' => true,
                        'show_unconfirmed_label' => true,
                        'confirmed_tooltip' => __('Account confirmed', 'wicket-acc'),
                        'unconfirmed_tooltip' => __('Account not confirmed', 'wicket-acc'),
                        'unconfirmed_label' => __('Account not confirmed', 'wicket-acc'),
                    ],
                    'seat_limit_message' => __('All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc'),
                    'remove_policy_callout' => [
                        'enabled' => false,
                        'placement' => 'above_members',
                        'title' => __('Remove Members', 'wicket-acc'),
                        'message' => __('To remove a member from your organization, please contact your association directly.', 'wicket-acc'),
                        'email' => '',
                    ],
                ],
                'member_view' => [
                    'use_unified' => true,
                    'search_clear_requires_submit' => false,
                ],
                'member_card_fields' => [
                    'name' => [
                        'enabled' => true,
                        'label' => __('Name', 'wicket-acc'),
                    ],
                    'job_title' => [
                        'enabled' => true,
                        'label' => __('Job Title', 'wicket-acc'),
                    ],
                    'description' => [
                        'enabled' => true,
                        'label' => __('Description', 'wicket-acc'),
                        'input_type' => 'textarea',
                    ],
                    'email' => [
                        'enabled' => true,
                        'label' => __('Email', 'wicket-acc'),
                    ],
                    'roles' => [
                        'enabled' => true,
                        'label' => __('Roles', 'wicket-acc'),
                    ],
                    'relationship_type' => [
                        'enabled' => false,
                        'label' => __('Relationship', 'wicket-acc'),
                    ],
                ],
            ],
            'member_addition_form' => [
                'layout' => 'full',
                'fields' => [
                    'first_name' => [
                        'enabled' => true,
                        'required' => true,
                        'label' => __('First Name', 'wicket-acc'),
                    ],
                    'last_name' => [
                        'enabled' => true,
                        'required' => true,
                        'label' => __('Last Name', 'wicket-acc'),
                    ],
                    'email' => [
                        'enabled' => true,
                        'required' => true,
                        'label' => __('Email Address', 'wicket-acc'),
                    ],
                    'relationship_type' => [
                        'enabled' => false,
                        'required' => false,
                        'label' => __('Relationship Type', 'wicket-acc'),
                    ],
                    'description' => [
                        'enabled' => true,
                        'required' => false,
                        'label' => __('Description', 'wicket-acc'),
                        'input_type' => 'textarea',
                    ],
                    'permissions' => [
                        'enabled' => true,
                        'required' => true,
                        'label' => __('Permissions', 'wicket-acc'),
                        'allowed_roles' => [],
                        'excluded_roles' => [
                            'Cchlmembercommunity',
                            'cchlmembercommunity',
                        ],
                    ],
                ],
                'allow_relationship_type_editing' => false,
            ],
            'bulk_upload' => [
                'batch_size' => 25,
                'columns' => [
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
                ],
                'relationship_type' => [
                    'required' => true,
                    'allowed_types' => [
                        'employee_staff',
                        'grade_4',
                    ],
                    'aliases' => [
                        'employee' => 'employee_staff',
                        'grade 4' => 'grade_4',
                        'grade_4' => 'grade_4',
                    ],
                ],
            ],
            'edit_permissions_modal' => [
                'allowed_roles' => [],
                'excluded_roles' => [
                    'Cchlmembercommunity',
                    'cchlmembercommunity',
                ],
            ],
            'member_edit' => [
                'require_active_membership_for_role_updates' => false,
            ],
            'notifications' => [
                'confirmation_email_from' => 'cchl@wicketcloud.com',
            ],
            'relationship_types' => [
                'custom_types' => [
                    'ceo' => __('CEO', 'wicket-acc'),
                    'primary_hr_contact' => __('Primary HR Contact', 'wicket-acc'),
                    'employee_staff' => __('Employee', 'wicket-acc'),
                    'member_contact' => __('Member Contact', 'wicket-acc'),
                ],
                'special_types' => [
                    'advertising_sponsor_contact' => __('Advertising/Sponsor Contact', 'wicket-acc'),
                    'advertising_sponsor_billing' => __('Advertising/Sponsor Billing Contact', 'wicket-acc'),
                ],
            ],
        ];

        return apply_filters('wicket/acc/orgman/config', $orgmanConfig);
    }
}
