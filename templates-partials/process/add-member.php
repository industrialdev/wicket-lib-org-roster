<?php

/**
 * Hypermedia partial for Add Member modal processing.
 *
 * Renders the form (GET) and processes submissions (POST).
 */

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;

if (!defined('ABSPATH')) {
    exit;
}

// Handle POST submissions
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ('POST' === strtoupper($request_method)) {
    // Validate nonce.
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'wicket-orgman-add-member')) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(__('Invalid or missing security token. Please refresh and try again.', 'wicket-acc'), '#add-member-messages-' . ($_POST['org_uuid'] ?? ''), ['addMemberSubmitting' => false, 'membersLoading' => false]);

        return;
    }

    $org_uuid = isset($_POST['org_uuid']) ? sanitize_text_field(wp_unslash($_POST['org_uuid'])) : '';
    $membership_uuid = isset($_POST['membership_id']) ? sanitize_text_field(wp_unslash($_POST['membership_id'])) : '';
    $org_dom_suffix = isset($_POST['org_dom_suffix'])
        ? sanitize_html_class((string) wp_unslash($_POST['org_dom_suffix']))
        : sanitize_html_class($org_uuid ?: 'default');

    if (empty($org_uuid)) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(__('Organization identifier missing.', 'wicket-acc'), '#add-member-messages-' . $org_dom_suffix, ['addMemberSubmitting' => false, 'membersLoading' => false]);

        return;
    }

    if (!OrgManagement\Helpers\PermissionHelper::can_add_members($org_uuid)) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('You do not have permission to add members to this organization.', 'wicket-acc'),
            '#add-member-messages-' . $org_dom_suffix,
            ['addMemberSubmitting' => false, 'membersLoading' => false]
        );

        return;
    }

    $requested_roster_mode = (new ConfigService())->get_roster_mode();
    if ($requested_roster_mode === 'membership_cycle' && empty($membership_uuid)) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('Membership UUID is required for membership cycle additions.', 'wicket-acc'),
            '#add-member-messages-' . $org_dom_suffix,
            ['addMemberSubmitting' => false, 'membersLoading' => false]
        );

        return;
    }

    $member_data = [
        'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
        'last_name'  => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
        'email'      => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        'phone'      => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
        'job_title'  => isset($_POST['job_title']) ? sanitize_text_field(wp_unslash($_POST['job_title'])) : '',
    ];

    // 1. Check for duplicates in the same membership UUID
    if (!empty($membership_uuid) && !empty($member_data['email'])) {
        try {
            $client = wicket_api_client();
            $filter_data = [
                'filter' => [
                    'organization_membership_uuid_in' => [$membership_uuid],
                    'person_emails_address_eq' => $member_data['email'],
                ],
            ];

            $response = $client->post('/person_memberships/query', ['json' => $filter_data]);

            if (!is_wp_error($response) && !empty($response['data'])) {
                // Check for active memberships
                foreach ($response['data'] as $p_membership) {
                    $is_active = $p_membership['attributes']['active'] ?? false;
                    if ($is_active) {
                        status_header(200);
                        OrgManagement\Helpers\DatastarSSE::renderError(
                            sprintf(__('A member with the email %s already exists in this membership.', 'wicket-acc'), '<strong>' . esc_html($member_data['email']) . '</strong>'),
                            '#add-member-messages-' . $org_dom_suffix,
                            ['addMemberSubmitting' => false, 'membersLoading' => false]
                        );

                        return;
                    }
                }
            }
        } catch (Throwable $e) {
            OrgManagement\Helpers\Helper::log_error('[OrgMan Debug] Duplicate check failed', ['error' => $e->getMessage()]);
        }
    }

    // Handle relationship type if provided
    $relationship_type = isset($_POST['relationship_type']) ? sanitize_text_field(wp_unslash($_POST['relationship_type'])) : '';
    $relationship_description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

    $roles = [];
    if (isset($_POST['roles']) && is_array($_POST['roles'])) {
        $roles = array_map(static function ($role) {
            return sanitize_text_field(wp_unslash($role));
        }, $_POST['roles']);
    }

    $orgman_config = OrgManagement\Config\OrgManConfig::get();
    $permissions_field_config = $orgman_config['member_addition_form']['fields']['permissions'] ?? [];
    $allowed_roles = is_array($permissions_field_config['allowed_roles'] ?? null)
        ? $permissions_field_config['allowed_roles']
        : [];
    $excluded_roles = is_array($permissions_field_config['excluded_roles'] ?? null)
        ? $permissions_field_config['excluded_roles']
        : [];

    $roles = OrgManagement\Helpers\PermissionHelper::filter_role_submission(
        $roles,
        $allowed_roles,
        $excluded_roles
    );

    try {
        $config_service = new ConfigService();
        $member_service = new MemberService($config_service);

        OrgManagement\Helpers\Helper::log_info('[OrgMan] Member addition attempt', [
            'org_uuid' => $org_uuid,
            'member_email' => $member_data['email'],
            'roles' => $roles,
        ]);

        $context = [
            'roles'            => $roles,
            'org_name'         => '',
            'membership_uuid'  => $membership_uuid,
            'relationship_type' => $relationship_type,
            'relationship_description' => $relationship_description,
        ];

        $result = $member_service->add_member($org_uuid, $member_data, $context);

        OrgManagement\Helpers\Helper::log_info('[OrgMan] Member addition completed', [
            'org_uuid' => $org_uuid,
            'success' => !is_wp_error($result),
            'error' => is_wp_error($result) ? $result->get_error_message() : null,
        ]);

        if (is_wp_error($result)) {
            status_header(200);
            OrgManagement\Helpers\DatastarSSE::renderError($result->get_error_message(), '#add-member-messages-' . $org_dom_suffix, ['addMemberSubmitting' => false, 'membersLoading' => false]);

            return;
        }

        // Handle automatic communication opt-in if configured
        $auto_opt_in = $orgman_config['member_addition']['auto_opt_in_communications'] ?? [];
        if (!empty($auto_opt_in['enabled']) && !empty($result['person_uuid'])) {
            if (function_exists('wicket_person_update_communication_preferences')) {
                $preferences = [];

                if (isset($auto_opt_in['email'])) {
                    $preferences['email'] = (bool) $auto_opt_in['email'];
                }

                if (!empty($auto_opt_in['sublists']) && is_array($auto_opt_in['sublists'])) {
                    $sublists = [];
                    foreach ($auto_opt_in['sublists'] as $sublist_key) {
                        $sublists[$sublist_key] = true;
                    }
                    $preferences['sublists'] = $sublists;
                }

                if (!empty($preferences)) {
                    $opt_in_result = wicket_person_update_communication_preferences($preferences, [
                        'person_uuid' => $result['person_uuid'],
                    ]);

                    if ($opt_in_result) {
                        OrgManagement\Helpers\Helper::log_info('[OrgMan] Auto-opt-in successful', [
                            'person_uuid' => $result['person_uuid'],
                            'preferences' => $preferences,
                        ]);
                    } else {
                        OrgManagement\Helpers\Helper::log_error('[OrgMan] Auto-opt-in failed', [
                            'person_uuid' => $result['person_uuid'],
                        ]);
                    }
                }
            } else {
                OrgManagement\Helpers\Helper::log_warning('[OrgMan] Auto-opt-in skipped: helper function missing');
            }
        }

        // Clear members cache for this organization after successful addition
        $cache_membership_uuid = $membership_uuid;
        if ($cache_membership_uuid === '') {
            $membership_service = new OrgManagement\Services\MembershipService();
            $cache_membership_uuid = (string) $membership_service->getMembershipForOrganization($org_uuid);
        }
        if ($cache_membership_uuid) {
            $orgman_instance = OrgManagement\OrgMan::get_instance();
            $orgman_instance->clear_members_cache($cache_membership_uuid);
        }

        // Success message
        $full_name = trim(($member_data['first_name'] ?? '') . ' ' . ($member_data['last_name'] ?? ''));
        $success_message = sprintf(
            esc_html__('Successfully added %1$s with email %2$s.', 'wicket-acc'),
            '<strong>' . esc_html($full_name) . '</strong>',
            '<strong>' . esc_html($member_data['email'] ?? '') . '</strong>'
        );

        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderSuccess($success_message, '#add-member-messages-' . $org_dom_suffix, [
            'addMemberSubmitting' => false,
            'membersLoading' => false,
            'addMemberSuccess' => true,
        ]);

        return;

    } catch (Throwable $e) {
        status_header(200);
        OrgManagement\Helpers\Helper::log_error('[OrgMan] Member addition failed: ' . $e->getMessage(), [
            'org_uuid' => $org_uuid,
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
        ]);
        OrgManagement\Helpers\DatastarSSE::renderError(__('An unexpected error occurred. Please try again.', 'wicket-acc'), '#add-member-messages-' . $org_dom_suffix, ['addMemberSubmitting' => false, 'membersLoading' => false]);

        return;
    }
}
