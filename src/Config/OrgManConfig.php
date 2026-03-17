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
            'access' => [
                'roles' => [
                    'owner' => 'membership_owner',
                    'manager' => 'membership_manager',
                    'editor' => 'org_editor',
                    'aliases' => [],
                    'labels' => [
                        'membership_manager' => __('Membership Manager', 'wicket-acc'),
                        'org_editor' => __('Org. Editor', 'wicket-acc'),
                        'membership_owner' => __('Membership Owner', 'wicket-acc'),
                    ],
                ],
                'permissions' => [
                    'organization_edit_roles' => [
                        'org_editor',
                    ],
                    'manage_member_roles' => [
                        'membership_manager',
                        'membership_owner',
                    ],
                    'add_member_roles' => [
                        'membership_manager',
                        'membership_owner',
                    ],
                    'remove_member_roles' => [
                        'membership_manager',
                        'membership_owner',
                    ],
                    'purchase_seat_roles' => [
                        'membership_owner',
                        'membership_manager',
                        'org_editor',
                    ],
                    'any_management_roles' => [
                        'org_editor',
                        'membership_manager',
                        'membership_owner',
                    ],
                    'prevent_owner_removal' => false,
                    'prevent_owner_assignment' => true,
                    'relationship_grants' => [
                        'enabled' => false,
                        'roles_by_type' => [
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
                    ],
                    'role_only_management_access' => [
                        'enabled' => false,
                        'allowed_roles' => [
                            'membership_owner',
                        ],
                    ],
                ],
            ],
            'membership' => [
                'strategy' => 'direct',
                'resolution' => [
                    'prefer_current_cycle' => false,
                ],
                'cycle' => [
                    'key' => 'membership_cycle',
                    'permissions' => [
                        'add_member_roles' => [
                            'membership_manager',
                        ],
                        'remove_member_roles' => [
                            'membership_manager',
                        ],
                        'purchase_seat_roles' => [
                            'membership_owner',
                            'membership_manager',
                            'org_editor',
                        ],
                    ],
                    'prevent_owner_removal' => true,
                    'require_explicit_membership_uuid' => true,
                ],
                'seat_limits' => [
                    'tier_max_assignments' => [],
                    'tier_name_case_sensitive' => false,
                ],
            ],
            'relationships' => [
                'defaults' => [
                    'type' => 'Position',
                ],
                'addition' => [
                    'type' => 'position',
                ],
                'filters' => [
                    'allowlist' => [],
                    'denylist' => [],
                ],
                'display' => [
                    'member_card_active_only' => false,
                ],
                'labels' => [
                    'custom' => [
                        'ceo' => __('CEO', 'wicket-acc'),
                        'primary_hr_contact' => __('Primary HR Contact', 'wicket-acc'),
                        'employee_staff' => __('Employee', 'wicket-acc'),
                        'member_contact' => __('Member Contact', 'wicket-acc'),
                    ],
                    'special' => [
                        'advertising_sponsor_contact' => __('Advertising/Sponsor Contact', 'wicket-acc'),
                        'advertising_sponsor_billing' => __('Advertising/Sponsor Billing Contact', 'wicket-acc'),
                    ],
                ],
            ],
            'member_management' => [
                'addition' => [
                    'auto_assign_roles' => [],
                    'base_member_role' => 'member',
                    'repair_stale_relationship_without_membership' => true,
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
                'forms' => [
                    'add_member' => [
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
                                'allowlist' => [],
                                'denylist' => [],
                            ],
                        ],
                        'allow_relationship_type_editing' => false,
                    ],
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
                'permissions_modal' => [
                    'allowlist' => [],
                    'denylist' => [],
                ],
                'edit' => [
                    'require_active_membership_for_role_updates' => false,
                ],
            ],
            'groups' => [
                'matching' => [
                    'tag_name' => 'Roster Management',
                    'tag_case_sensitive' => false,
                ],
                'roles' => [
                    'management' => [
                        'president',
                        'delegate',
                        'alternate_delegate',
                        'council_delegate',
                        'council_alternate_delegate',
                        'correspondent',
                    ],
                    'roster' => [
                        'member',
                        'observer',
                    ],
                    'member' => 'member',
                    'observer' => 'observer',
                    'seat_limited' => ['member'],
                ],
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
                    // Keep this at the base-plugin UTC instant format unless a site explicitly needs a custom API format.
                    'end_date_format' => 'Y-m-d\\TH:i:s\\Z',
                ],
                'presentation' => [
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
            'presentation' => [
                'organization_list' => [
                    'page_size' => 5,
                    'use_custom_title' => false,
                    'custom_title' => '',
                ],
                'relationships' => [
                    'show_type' => false,
                    'show_special_types' => false,
                ],
                'member_list' => [
                    'use_unified' => true,
                    'show_edit_permissions' => true,
                    'show_remove_button' => true,
                    'show_bulk_upload' => false,
                    'display_roles' => [
                        'allowlist' => [],
                        'denylist' => [],
                    ],
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
                'member_card' => [
                    'fields' => [
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
            ],
            'integrations' => [
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
                'notifications' => [
                    'confirmation_email_from' => 'no-reply@wicketcloud.com',
                ],
            ],
            'platform' => [
                'cache' => [
                    'enabled' => false,
                    'duration' => 5 * 60,
                ],
            ],
        ];

        return apply_filters('wicket/acc/orgman/config', $orgmanConfig);
    }
}
