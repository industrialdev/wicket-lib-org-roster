<?php

/**
 * Hypermedia partial for Add Group Member processing.
 */

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\GroupService;
use OrgManagement\Services\MemberService;
use OrgManagement\Services\MembershipService;

if (!defined('ABSPATH')) {
    exit;
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ('POST' !== strtoupper($request_method)) {
    return;
}

$logger = wc_get_logger();
$log_context = [
    'source' => 'wicket-orgroster',
    'action' => 'add_group_member',
    'user_id' => get_current_user_id(),
];

$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
if (!$nonce || !wp_verify_nonce($nonce, 'wicket-orgman-add-group-member')) {
    $logger->warning('[OrgRoster] Add group member invalid nonce', $log_context);
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(__('Invalid or missing security token. Please refresh and try again.', 'wicket-acc'), '#group-member-messages', ['membersLoading' => false]);

    return;
}

$group_uuid = isset($_POST['group_uuid']) ? sanitize_text_field(wp_unslash($_POST['group_uuid'])) : '';
$org_uuid = isset($_POST['org_uuid']) ? sanitize_text_field(wp_unslash($_POST['org_uuid'])) : '';
$role = isset($_POST['role']) ? sanitize_text_field(wp_unslash($_POST['role'])) : '';

$log_context['group_uuid'] = $group_uuid;
$log_context['org_uuid'] = $org_uuid;
$log_context['role'] = $role;
$logger->info('[OrgRoster] Add group member request received', $log_context);

if (empty($group_uuid)) {
    $logger->error('[OrgRoster] Add group member missing group_uuid', $log_context);
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(__('Group identifier missing.', 'wicket-acc'), '#group-member-messages', ['membersLoading' => false]);

    return;
}

$member_data = [
    'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
    'last_name'  => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
    'email'      => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
];

$log_context['member_email'] = $member_data['email'];

$group_service = new GroupService();
$current_user = wp_get_current_user();
$access = $group_service->can_manage_group($group_uuid, (string) $current_user->user_login);
if (empty($access['allowed'])) {
    $logger->warning('[OrgRoster] Add group member access denied', $log_context);
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(__('You do not have permission to manage this group.', 'wicket-acc'), '#group-member-messages', ['membersLoading' => false]);

    return;
}

// Enforce seat availability when org membership is available.
if (!empty($org_uuid)) {
    $membership_service = new MembershipService();
    $membership_uuid = $membership_service->getMembershipForOrganization($org_uuid);
    if ($membership_uuid) {
        $membership_data = $membership_service->getOrgMembershipData($membership_uuid);
        if ($membership_data && isset($membership_data['data']['attributes'])) {
            $max_seats = $membership_data['data']['attributes']['max_assignments'] ?? null;
            $active_seats = (int) ($membership_data['data']['attributes']['active_assignments_count'] ?? 0);
            if ($max_seats !== null && $active_seats >= (int) $max_seats) {
                $logger->info('[OrgRoster] Add group member blocked by seat limit', array_merge($log_context, [
                    'max_seats' => $max_seats,
                    'active_seats' => $active_seats,
                ]));
                status_header(200);
                OrgManagement\Helpers\DatastarSSE::renderError(__('No seats available for this organization.', 'wicket-acc'), '#group-member-messages', ['membersLoading' => false]);

                return;
            }
        }
    }
}

$config_service = new ConfigService();
$member_service = new MemberService($config_service);

$context = [
    'group_uuid' => $group_uuid,
    'role' => $role,
];

$result = $member_service->add_member($org_uuid, $member_data, $context);
if (is_wp_error($result)) {
    $logger->error('[OrgRoster] Add group member failed', array_merge($log_context, [
        'error' => $result->get_error_message(),
    ]));
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError($result->get_error_message(), '#group-member-messages', ['membersLoading' => false]);

    return;
}

$logger->info('[OrgRoster] Add group member succeeded', $log_context);

$full_name = trim(($member_data['first_name'] ?? '') . ' ' . ($member_data['last_name'] ?? ''));
$success_message = sprintf(
    esc_html__('Successfully added %1$s with email %2$s.', 'wicket-acc'),
    '<strong>' . esc_html($full_name) . '</strong>',
    '<strong>' . esc_html($member_data['email'] ?? '') . '</strong>'
);

status_header(200);
OrgManagement\Helpers\DatastarSSE::renderSuccess($success_message, '#group-member-messages', [
    'membersLoading' => false,
]);

return;
