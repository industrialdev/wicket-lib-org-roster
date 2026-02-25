<?php

/**
 * Hypermedia endpoint for group members list.
 */

use OrgManagement\Services\GroupService;

if (!defined('ABSPATH')) {
    exit;
}

$group_uuid = isset($_GET['group_uuid']) ? sanitize_text_field((string) $_GET['group_uuid']) : '';
$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field((string) $_GET['org_uuid']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$query = isset($_GET['query']) ? sanitize_text_field((string) $_GET['query']) : '';

if (empty($group_uuid)) {
    echo '<p class="wt_text-gray-500">' . esc_html__('No group selected.', 'wicket-acc') . '</p>';

    return;
}

$group_service = new GroupService();
$current_user = wp_get_current_user();
$access = $group_service->can_manage_group($group_uuid, (string) $current_user->user_login);
if (empty($access['allowed'])) {
    echo '<p class="wt_text-gray-500">' . esc_html__('You do not have permission to manage this group.', 'wicket-acc') . '</p>';

    return;
}

$org_identifier = (string) ($access['org_identifier'] ?? '');
$org_uuid = $org_uuid ?: (string) ($access['org_uuid'] ?? '');
if (empty($org_uuid) && function_exists('wicket_get_group')) {
    $group_data = wicket_get_group($group_uuid);
    if (is_array($group_data)) {
        $org_uuid = $group_data['data']['relationships']['organization']['data']['id'] ?? $org_uuid;
    }
}
$result = $group_service->get_group_members($group_uuid, $org_identifier, [
    'page' => $page,
    'size' => $group_service->get_group_member_page_size(),
    'query' => $query,
    'org_uuid' => $org_uuid,
]);

$group_members = $result['members'] ?? [];
$group_pagination = $result['pagination'] ?? [];
$group_query = $result['query'] ?? '';
$group_members_list_endpoint = \OrgManagement\Helpers\template_url() . 'group-members-list';
$group_members_list_target = 'group-members-list-container-' . sanitize_html_class($group_uuid);

$orgman_config = \OrgManagement\Config\get_config();
$use_unified_member_list = (bool) ($orgman_config['groups']['ui']['use_unified_member_list'] ?? false);
if ($use_unified_member_list) {
    $mode = 'groups';
    $members = $group_members;
    $pagination = $group_pagination;
    $query = $group_query;
    $members_list_endpoint = $group_members_list_endpoint;
    $members_list_target = $group_members_list_target;
    $membership_service = new OrgManagement\Services\MembershipService();
    $membership_uuid = $org_uuid ? $membership_service->getMembershipForOrganization($org_uuid) : '';
    $show_edit_permissions = (bool) ($orgman_config['groups']['ui']['show_edit_permissions'] ?? false);
    $show_account_status = (bool) (($orgman_config['ui']['member_list']['account_status']['enabled'] ?? true));
    $show_add_member_button = true;
    $show_remove_button = true;
    include __DIR__ . '/members-list-unified.php';
} else {
    include __DIR__ . '/group-members-list.php';
}
