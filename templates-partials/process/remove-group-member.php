<?php
/**
 * Hypermedia partial for Remove Group Member processing.
 *
 * @package OrgManagement
 */

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;
use OrgManagement\Services\GroupService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ( 'POST' !== strtoupper( $request_method ) ) {
    return;
}

$logger = wc_get_logger();
$log_context = [
    'source' => 'wicket-orgroster',
    'action' => 'remove_group_member',
    'user_id' => get_current_user_id(),
];

$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wicket-orgman-remove-group-member' ) ) {
    $logger->warning('[OrgRoster] Remove group member invalid nonce', $log_context);
    status_header( 200 );
    OrgManagement\Helpers\DatastarSSE::renderError( __( 'Invalid or missing security token. Please refresh and try again.', 'wicket-acc' ), '#remove-member-messages', [ 'removeMemberSubmitting' => false, 'membersLoading' => false ] );
    return;
}

$group_uuid = isset( $_POST['group_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['group_uuid'] ) ) : '';
$org_uuid = isset( $_POST['org_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['org_uuid'] ) ) : '';
$person_uuid = isset( $_POST['person_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['person_uuid'] ) ) : '';
$group_member_id = isset( $_POST['group_member_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_member_id'] ) ) : '';
$role = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';

$log_context['group_uuid'] = $group_uuid;
$log_context['org_uuid'] = $org_uuid;
$log_context['person_uuid'] = $person_uuid;
$log_context['group_member_id'] = $group_member_id;
$log_context['role'] = $role;
$logger->info('[OrgRoster] Remove group member request received', $log_context);

if ( empty( $group_uuid ) || empty( $person_uuid ) ) {
    $logger->error('[OrgRoster] Remove group member missing identifiers', $log_context);
    status_header( 200 );
    OrgManagement\Helpers\DatastarSSE::renderError( __( 'Missing group or member identifiers.', 'wicket-acc' ), '#remove-member-messages', [ 'removeMemberSubmitting' => false, 'membersLoading' => false ] );
    return;
}

$group_service = new GroupService();
$current_user = wp_get_current_user();
$access = $group_service->can_manage_group( $group_uuid, (string) $current_user->user_login );
if ( empty( $access['allowed'] ) ) {
    $logger->warning('[OrgRoster] Remove group member access denied', $log_context);
    status_header( 200 );
    OrgManagement\Helpers\DatastarSSE::renderError( __( 'You do not have permission to manage this group.', 'wicket-acc' ), '#remove-member-messages', [ 'removeMemberSubmitting' => false, 'membersLoading' => false ] );
    return;
}

$config_service = new ConfigService();
$member_service = new MemberService( $config_service );

$context = [
    'group_uuid' => $group_uuid,
    'group_member_id' => $group_member_id,
    'role' => $role,
];

$result = $member_service->remove_member( $org_uuid, $person_uuid, $context );
if ( is_wp_error( $result ) ) {
    $logger->error('[OrgRoster] Remove group member failed', array_merge($log_context, [
        'error' => $result->get_error_message(),
    ]));
    status_header( 200 );
    OrgManagement\Helpers\DatastarSSE::renderError( $result->get_error_message(), '#remove-member-messages', [ 'removeMemberSubmitting' => false, 'membersLoading' => false ] );
    return;
}

$logger->info('[OrgRoster] Remove group member succeeded', $log_context);

status_header( 200 );
OrgManagement\Helpers\DatastarSSE::renderSuccess( __( 'Group member removed successfully.', 'wicket-acc' ), '#remove-member-messages', [ 'removeMemberSubmitting' => false, 'membersLoading' => false ] );
return;
