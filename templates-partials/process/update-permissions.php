<?php

/**
 * Hypermedia partial for Update Permissions modal processing.
 *
 * Renders the form (GET) and processes submissions (POST).
 *
 * @package OrgManagement
 */

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;

if (! defined('ABSPATH')) {
    exit;
}


// Handle POST submissions
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ('POST' === strtoupper($request_method)) {
    // Validate nonce.
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wicket-orgman-update-permissions' ) ) {
        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'Invalid or missing security token. Please refresh and try again.', 'wicket-acc' ), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
        return;
    }

    $org_uuid = isset($_POST['org_uuid']) ? sanitize_text_field(wp_unslash($_POST['org_uuid'])) : '';
    $membership_uuid = isset($_POST['membership_uuid']) ? sanitize_text_field(wp_unslash($_POST['membership_uuid'])) : '';
    $person_uuid = isset($_POST['person_uuid']) ? sanitize_text_field(wp_unslash($_POST['person_uuid'])) : '';

    if ( empty( $org_uuid ) || empty( $membership_uuid ) || empty( $person_uuid ) ) {
        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'Organization UUID, membership UUID, and person UUID are required.', 'wicket-acc' ), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
        return;
    }

    $roles = [];
    if ( isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ) {
        $roles = array_map( static function ( $role ) {
            return sanitize_text_field( wp_unslash( $role ) );
        }, $_POST['roles'] );
    }

    // Get relationship type if provided
    $relationship_type = isset($_POST['relationship_type']) ? sanitize_text_field(wp_unslash($_POST['relationship_type'])) : '';
    $description = array_key_exists('description', $_POST) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : null;

    try {
        $config_service = new ConfigService();
        $member_service = new MemberService( $config_service );

        // First update relationship type if provided and enabled
        $config = \OrgManagement\Config\get_config();
        $allow_relationship_editing = $config['member_addition_form']['allow_relationship_type_editing'] ?? false;
        $form_fields = $config['member_addition_form']['fields'] ?? [];
        $allow_description_editing = $form_fields['description']['enabled'] ?? false;

        if ($allow_relationship_editing && !empty($relationship_type)) {
            $relationship_result = $member_service->update_member_relationship($person_uuid, $org_uuid, $relationship_type);

            if (is_wp_error($relationship_result)) {
                status_header( 200 );
                OrgManagement\Helpers\DatastarSSE::renderError( $relationship_result->get_error_message(), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
                return;
            }
        }

        if ($allow_description_editing && $description !== null) {
            $description_result = $member_service->update_member_description($person_uuid, $org_uuid, $description);

            if (is_wp_error($description_result)) {
                status_header( 200 );
                OrgManagement\Helpers\DatastarSSE::renderError( $description_result->get_error_message(), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
                return;
            }
        }

        // Then update roles
        $result = $member_service->update_member_roles( $person_uuid, $org_uuid, $membership_uuid, $roles );

        if ( is_wp_error( $result ) ) {
            status_header( 200 );
            OrgManagement\Helpers\DatastarSSE::renderError( $result->get_error_message(), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
            return;
        }

        // Success message
        $full_name = trim( ( $result['first_name'] ?? '' ) . ' ' . ( $result['last_name'] ?? '' ) );
        $success_message = sprintf(
            esc_html__( 'Successfully updated permissions for %1$s.', 'wicket-acc' ),
            '<strong>' . esc_html( $full_name ) . '</strong>'
        );

        status_header( 200 );
        OrgManagement\Helpers\DatastarSSE::renderSuccess( $success_message, '#update-permissions-messages', [
            'editPermissionsSubmitting' => false,
            'editPermissionsSuccess' => true
        ] );
        return;

    } catch ( \Throwable $e ) {
        status_header( 200 );
        OrgManagement\Helpers\Helper::log_error( '[OrgMan] update-permissions modal failed: ' . $e->getMessage(), [ 'org_uuid' => $org_uuid, 'membership_uuid' => $membership_uuid, 'person_uuid' => $person_uuid ] );
        OrgManagement\Helpers\DatastarSSE::renderError( __( 'An unexpected error occurred. Please try again.', 'wicket-acc' ), '#update-permissions-messages', ['editPermissionsSubmitting' => false] );
        return;
    }
}

// For GET requests, this file should not be accessed directly
status_header( 405 );
echo json_encode( [ 'error' => 'Method not allowed' ] );
