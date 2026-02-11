<?php

/**
 * Hypermedia partial for Remove Member modal processing.
 *
 * Renders the form (GET) and processes submissions (POST).
 *
 * @package OrgManagement
 */

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\ConnectionService;

if (! defined('ABSPATH')) {
    exit;
}


// Handle POST submissions
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ('POST' === strtoupper($request_method)) {
    // Validate nonce.
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wicket-orgman-remove-member' ) ) {
        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'Invalid or missing security token. Please refresh and try again.', 'wicket-acc' ), '#remove-member-messages', ['removeMemberSubmitting' => false, 'membersLoading' => false] );
        return;
    }

    $org_uuid = isset($_POST['org_uuid']) ? sanitize_text_field(wp_unslash($_POST['org_uuid'])) : '';
    $membership_uuid = isset($_POST['membership_uuid']) ? sanitize_text_field(wp_unslash($_POST['membership_uuid'])) : '';
    $person_uuid = isset($_POST['person_uuid']) ? sanitize_text_field(wp_unslash($_POST['person_uuid'])) : '';
    $person_name = isset($_POST['person_name']) ? sanitize_text_field(wp_unslash($_POST['person_name'])) : '';
    $person_email = isset($_POST['person_email']) ? sanitize_email(wp_unslash($_POST['person_email'])) : '';
    $person_connection_ids = isset($_POST['connection_id']) ? sanitize_text_field(wp_unslash($_POST['connection_id'])) : '';
    $person_membership_id = isset($_POST['person_membership_id']) ? sanitize_text_field(wp_unslash($_POST['person_membership_id'])) : '';

    // Require org_uuid and person_uuid, but allow either connection_id OR person_membership_id
    if ( empty( $org_uuid ) || empty( $person_uuid ) || ( empty( $person_connection_ids ) && empty( $person_membership_id ) ) ) {
        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'Organization UUID, person UUID, and connection IDs are required.', 'wicket-acc' ), '#remove-member-messages', ['removeMemberSubmitting' => false, 'membersLoading' => false] );
        return;
    }

    // Check if user has permission to remove members
    if (! \OrgManagement\Helpers\PermissionHelper::can_remove_members($org_uuid)) {
        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'You do not have permission to remove members from this organization.', 'wicket-acc' ), '#remove-member-messages', ['removeMemberSubmitting' => false, 'membersLoading' => false] );
        return;
    }

    // Handle comma-separated connection IDs (like legacy system)
    if (is_string($person_connection_ids) && strpos($person_connection_ids, ',') !== false) {
        $connection_ids = explode(',', $person_connection_ids);
    } else {
        $connection_ids = [$person_connection_ids];
    }
    $connection_ids = array_filter(array_map('trim', $connection_ids));

    try {
        $config_service = new ConfigService();
        $roster_mode = (string) $config_service->get_roster_mode();
        $membership_service = new \OrgManagement\Services\MembershipService();
        $permission_service = new \OrgManagement\Services\PermissionService();
        $organization_service = new \OrgManagement\Services\OrganizationService();

        if ($roster_mode === 'membership_cycle' && empty($membership_uuid)) {
            status_header(200);
            OrgManagement\Helpers\DatastarSSE::renderError(
                __('Membership UUID is required for membership cycle removals.', 'wicket-acc'),
                '#remove-member-messages',
                ['removeMemberSubmitting' => false, 'membersLoading' => false]
            );
            return;
        }

        if ($roster_mode === 'membership_cycle') {
            $cycle_config = \OrgManagement\Config\get_config()['membership_cycle'] ?? [];
            $prevent_owner_removal = (bool) ($cycle_config['permissions']['prevent_owner_removal'] ?? true);
            if ($prevent_owner_removal) {
                $org_owner = $organization_service->get_organization_owner($org_uuid);
                if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                    status_header(200);
                    OrgManagement\Helpers\DatastarSSE::renderError(
                        __('The organization owner (Primary Member) cannot be removed.', 'wicket-acc'),
                        '#remove-member-messages',
                        ['removeMemberSubmitting' => false, 'membersLoading' => false]
                    );
                    return;
                }
            }
        }

        // Get person name for success message
        $member_name = 'Member';
        if (!empty($person_name)) {
            $member_name = $person_name;
        } elseif (function_exists('wicket_get_person_by_id')) {
            $person_data = wicket_get_person_by_id($person_uuid);
            if ($person_data) {
                // Handle both array and object formats
                $attributes = null;
                if (is_array($person_data) && !empty($person_data['data']['attributes'])) {
                    $attributes = $person_data['data']['attributes'];
                } elseif (is_object($person_data) && method_exists($person_data, 'data') && method_exists($person_data->data(), 'attributes')) {
                    $attributes = $person_data->data()->attributes();
                }

                if ($attributes) {
                    $first_name = $attributes['first_name'] ?? '';
                    $last_name = $attributes['last_name'] ?? '';
                    $member_name = trim( $first_name . ' ' . $last_name );
                    if (empty($member_name)) {
                        $member_name = 'Member';
                    }
                }
            }
        }

        $removal_success = false;
        $connection_service = new ConnectionService();

        // 1. End all person memberships for this person/email in this organization membership
        if (!empty($membership_uuid) && !empty($person_uuid)) {
            \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Searching for person memberships by ID', [
                'membership_uuid' => $membership_uuid,
                'person_uuid' => $person_uuid
            ]);

            try {
                $client = wicket_api_client();
                $filter_data = [
                    'filter' => [
                        'organization_membership_uuid_in' => [ $membership_uuid ],
                        'person_id_eq' => $person_uuid
                    ]
                ];

                $response = $client->post( '/person_memberships/query', [ 'json' => $filter_data ] );

                if (!is_wp_error($response) && !empty($response['data'])) {
                    \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Found memberships count by ID: ' . count($response['data']));
                    foreach ($response['data'] as $p_membership) {
                        $p_membership_id = $p_membership['id'] ?? null;
                        $p_membership_active = $p_membership['attributes']['active'] ?? false;

                        \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Processing extra p_membership', ['id' => $p_membership_id, 'active' => $p_membership_active]);

                        if ($p_membership_id && $p_membership_active) {
                            $result = $membership_service->endPersonMembershipToday($p_membership_id);
                            if (!is_wp_error($result)) {
                                $removal_success = true;
                                \OrgManagement\Helpers\Helper::log_info('[OrgMan] Successfully end-dated extra person membership by ID', [
                                    'person_membership_id' => $p_membership_id
                                ]);
                            } else {
                                \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to end extra membership', [
                                    'id' => $p_membership_id,
                                    'error' => $result->get_error_message()
                                ]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \OrgManagement\Helpers\Helper::log_error('[OrgMan Debug] Membership query exception', ['error' => $e->getMessage()]);
            }
        }

        // 2. Fallback or explicit removal of the provided person membership ID
        if (!empty($person_membership_id)) {
            $result = $membership_service->endPersonMembershipToday( $person_membership_id );

            if ( is_wp_error( $result ) ) {
                \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to end-date primary person membership', [
                    'person_membership_id' => $person_membership_id,
                    'error' => $result->get_error_message()
                ]);
            } else {
                $removal_success = true;
                \OrgManagement\Helpers\Helper::log_info('[OrgMan] Successfully end-dated primary person membership', [
                    'person_membership_id' => $person_membership_id
                ]);
            }
        }

        \OrgManagement\Helpers\Helper::log_debug('[OrgMan Debug] Member removal roster mode', ['roster_mode' => $roster_mode, 'person_uuid' => $person_uuid]);

        if ($roster_mode === 'membership_cycle') {
            if (!$removal_success) {
                status_header(200);
                OrgManagement\Helpers\DatastarSSE::renderError(
                    __('Failed to remove member. Person membership ID is required.', 'wicket-acc'),
                    '#remove-member-messages',
                    ['removeMemberSubmitting' => false, 'membersLoading' => false]
                );
                return;
            }

            if (!empty($membership_uuid)) {
                $orgman_instance = \OrgManagement\OrgMan::get_instance();
                $orgman_instance->clear_members_cache($membership_uuid);
            }

            $success_message = sprintf(
                esc_html__( 'Successfully removed %1$s from the organization.', 'wicket-acc' ),
                '<strong>' . esc_html( $member_name ) . '</strong>'
            );

            status_header(200);
            OrgManagement\Helpers\DatastarSSE::renderSuccess($success_message, '#remove-member-messages', [
                'removeMemberSubmitting' => false,
                'removeMemberSuccess' => true,
                'membersLoading' => false
            ], 'remove-countdown');
            return;
        }

        // 3. End ALL connections for this person to this organization
        if (!empty($person_uuid) && $roster_mode !== 'direct') {
            \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Searching for person connections', ['person_uuid' => $person_uuid, 'org_uuid' => $org_uuid]);
            $connections = $connection_service->getPersonConnectionsById($person_uuid);
            if (!empty($connections['data'])) {
                \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Found connections count: ' . count($connections['data']));
                foreach ($connections['data'] as $conn) {
                    $conn_org_id = $conn['relationships']['organization']['data']['id'] ?? null;
                    $conn_id = $conn['id'] ?? null;
                    $conn_active = $conn['attributes']['active'] ?? false;

                    \OrgManagement\Helpers\Helper::log_info('[OrgMan Debug] Processing extra connection', ['id' => $conn_id, 'org_id' => $conn_org_id, 'active' => $conn_active]);

                    if ($conn_org_id === $org_uuid && $conn_id && $conn_active) {
                        $result = $connection_service->endRelationshipToday($person_uuid, $conn_id, $org_uuid);
                        if (!is_wp_error($result)) {
                            $removal_success = true;
                            \OrgManagement\Helpers\Helper::log_info('[OrgMan] Successfully ended extra relationship', [
                                'person_uuid' => $person_uuid,
                                'connection_id' => $conn_id,
                                'org_uuid' => $org_uuid
                            ]);
                        } else {
                            \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to end extra connection', ['id' => $conn_id, 'error' => $result->get_error_message()]);
                        }
                    }
                }
            }
        }

        // 4. Fallback or explicit removal of the provided connection IDs
        if (!empty($connection_ids) && $roster_mode !== 'direct') {
            foreach ($connection_ids as $connection_id) {
                if (empty($connection_id)) continue;
                $result = $connection_service->endRelationshipToday($person_uuid, $connection_id, $org_uuid);

                if (is_wp_error($result)) {
                    \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to end primary relationship', [
                        'person_uuid' => $person_uuid,
                        'connection_id' => $connection_id,
                        'org_uuid' => $org_uuid,
                        'error' => $result->get_error_message()
                    ]);
                } else {
                    $removal_success = true;
                    \OrgManagement\Helpers\Helper::log_info('[OrgMan] Successfully ended primary relationship', [
                        'person_uuid' => $person_uuid,
                        'connection_id' => $connection_id,
                        'org_uuid' => $org_uuid
                    ]);
                }
            }
        }

        // 2. Remove all org-scoped roles
        $roles_to_remove = $permission_service->get_person_current_roles_by_org_id( $person_uuid, $org_uuid );
        if ( ! empty( $roles_to_remove ) ) {
            $role_removal_result = $permission_service->remove_person_roles_from_org( $person_uuid, $roles_to_remove, $org_uuid );

            if ( is_wp_error( $role_removal_result ) ) {
                \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to remove roles', [
                    'person_uuid' => $person_uuid,
                    'org_uuid' => $org_uuid,
                    'roles' => $roles_to_remove,
                    'error' => $role_removal_result->get_error_message()
                ]);
            } else {
                \OrgManagement\Helpers\Helper::log_info('[OrgMan] Successfully removed roles', [
                    'person_uuid' => $person_uuid,
                    'org_uuid' => $org_uuid,
                    'roles' => $roles_to_remove
                ]);
            }
        }

        // If still no success, throw an error
        if (!$removal_success) {
            status_header( 200 );
            OrgManagement\Helpers\DatastarSSE::renderError( __( 'Failed to remove member. Person membership ID is required.', 'wicket-acc' ), '#remove-member-messages', ['removeMemberSubmitting' => false, 'membersLoading' => false] );
            return;
        }

        // Success message
        $success_message = sprintf(
            esc_html__( 'Successfully removed %1$s from the organization.', 'wicket-acc' ),
            '<strong>' . esc_html( $member_name ) . '</strong>'
        );

        // Clear members cache for this organization after successful removal
        if ($removal_success && !empty($membership_uuid)) {
            $orgman_instance = \OrgManagement\OrgMan::get_instance();
            $orgman_instance->clear_members_cache($membership_uuid);
        }

        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderSuccess( $success_message, '#remove-member-messages', [
            'removeMemberSubmitting' => false,
            'removeMemberSuccess' => true,
            'membersLoading' => false
        ], 'remove-countdown' );
        return;

    } catch ( \Throwable $e ) {
        status_header( 200 );
        OrgManagement\Helpers\Helper::log_error( '[OrgMan] remove-member modal failed: ' . $e->getMessage(), [ 'org_uuid' => $org_uuid, 'person_uuid' => $person_uuid, 'connection_ids' => $person_connection_ids ] );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'An unexpected error occurred. Please try again.', 'wicket-acc' ), '#remove-member-messages', ['removeMemberSubmitting' => false, 'membersLoading' => false] );
        return;
    }
}

// For GET requests, this file should not be accessed directly
status_header( 405 );
echo json_encode( [ 'error' => 'Method not allowed' ] );
