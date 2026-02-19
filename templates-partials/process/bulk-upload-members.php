<?php

declare(strict_types=1);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\GroupService;
use OrgManagement\Services\MemberService;
use OrgManagement\Services\MembershipService;

if (!defined('ABSPATH')) {
    exit;
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ('POST' !== strtoupper((string) $request_method)) {
    return;
}

$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
if (!$nonce || !wp_verify_nonce($nonce, 'wicket-orgman-bulk-upload-members')) {
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('Invalid or missing security token. Please refresh and try again.', 'wicket-acc'),
        '#bulk-upload-messages-default',
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

$org_uuid = isset($_POST['org_uuid']) ? sanitize_text_field(wp_unslash($_POST['org_uuid'])) : '';
$group_uuid = isset($_POST['group_uuid']) ? sanitize_text_field(wp_unslash($_POST['group_uuid'])) : '';
$membership_uuid = isset($_POST['membership_uuid']) ? sanitize_text_field(wp_unslash($_POST['membership_uuid'])) : '';
$message_dom_suffix_source = $org_uuid !== '' ? $org_uuid : ($group_uuid !== '' ? $group_uuid : 'default');
$org_dom_suffix = sanitize_html_class($message_dom_suffix_source);
$message_target = '#bulk-upload-messages-' . $org_dom_suffix;
$orgman_config = \OrgManagement\Config\get_config();
$member_list_config = is_array($orgman_config['ui']['member_list'] ?? null)
    ? $orgman_config['ui']['member_list']
    : [];
$bulk_upload_enabled = (bool) ($member_list_config['show_bulk_upload'] ?? false);

if (!$bulk_upload_enabled) {
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('Bulk upload is disabled.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

if (empty($_FILES['bulk_file']['tmp_name']) || !is_uploaded_file((string) $_FILES['bulk_file']['tmp_name'])) {
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('Please select a valid CSV file.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

$config_service = new ConfigService();
$roster_mode = (string) $config_service->get_roster_mode();
$membership_service = new MembershipService();
$member_service = new MemberService($config_service);

$group_access = [
    'allowed' => false,
    'org_uuid' => '',
    'org_identifier' => '',
    'role_slug' => '',
];
$group_service = null;

if ($roster_mode === 'groups') {
    if ($group_uuid === '') {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('Group identifier missing.', 'wicket-acc'),
            $message_target,
            ['membersLoading' => false, 'bulkUploadSubmitting' => false]
        );

        return;
    }

    $current_user = wp_get_current_user();
    $person_uuid = $current_user ? (string) $current_user->user_login : '';
    $group_service = new GroupService();
    $group_access = $group_service->can_manage_group($group_uuid, $person_uuid);

    if (empty($group_access['allowed'])) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('You do not have permission to bulk add members to this group.', 'wicket-acc'),
            $message_target,
            ['membersLoading' => false, 'bulkUploadSubmitting' => false]
        );

        return;
    }

    $resolved_org_uuid = (string) ($group_access['org_uuid'] ?? '');
    if ($resolved_org_uuid !== '') {
        $org_uuid = $resolved_org_uuid;
    }
} else {
    if (empty($org_uuid)) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('Organization identifier missing.', 'wicket-acc'),
            $message_target,
            ['membersLoading' => false, 'bulkUploadSubmitting' => false]
        );

        return;
    }

    if (!OrgManagement\Helpers\PermissionHelper::can_add_members($org_uuid)) {
        status_header(200);
        OrgManagement\Helpers\DatastarSSE::renderError(
            __('You do not have permission to bulk add members to this organization.', 'wicket-acc'),
            $message_target,
            ['membersLoading' => false, 'bulkUploadSubmitting' => false]
        );

        return;
    }
}

if ($membership_uuid === '' && $org_uuid !== '') {
    $membership_uuid = (string) $membership_service->getMembershipForOrganization($org_uuid);
}

if ($roster_mode !== 'groups' && $membership_uuid === '') {
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('No active organization membership was found for this organization.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

/**
 * @param array<int, string> $headers
 * @return array<string, int>
 */
$resolve_header_index = static function (array $headers): array {
    $normalized = [];
    foreach ($headers as $idx => $header) {
        $key = strtolower(trim((string) $header));
        $key = str_replace(['_', '-'], ' ', $key);
        $normalized[$key] = (int) $idx;
    }

    $index_map = [
        'first_name' => -1,
        'last_name' => -1,
        'email' => -1,
        'roles' => -1,
    ];

    $first_name_keys = ['first name', 'firstname', 'first'];
    $last_name_keys = ['last name', 'lastname', 'last'];
    $email_keys = ['email address', 'email', 'e-mail'];
    $roles_keys = ['roles', 'permissions', 'role'];

    foreach ($first_name_keys as $key) {
        if (isset($normalized[$key])) {
            $index_map['first_name'] = $normalized[$key];
            break;
        }
    }

    foreach ($last_name_keys as $key) {
        if (isset($normalized[$key])) {
            $index_map['last_name'] = $normalized[$key];
            break;
        }
    }

    foreach ($email_keys as $key) {
        if (isset($normalized[$key])) {
            $index_map['email'] = $normalized[$key];
            break;
        }
    }

    foreach ($roles_keys as $key) {
        if (isset($normalized[$key])) {
            $index_map['roles'] = $normalized[$key];
            break;
        }
    }

    return $index_map;
};

/**
 * @param string $membership_uuid
 * @param string $email
 * @return bool
 */
$active_membership_exists = static function (string $membership_uuid, string $email): bool {
    if ($membership_uuid === '' || $email === '' || !function_exists('wicket_api_client')) {
        return false;
    }

    try {
        $client = wicket_api_client();
        $response = $client->post('/person_memberships/query', [
            'json' => [
                'filter' => [
                    'organization_membership_uuid_in' => [$membership_uuid],
                    'person_emails_address_eq' => $email,
                ],
            ],
        ]);

        if (is_wp_error($response) || empty($response['data']) || !is_array($response['data'])) {
            return false;
        }

        foreach ($response['data'] as $person_membership) {
            $is_active = (bool) ($person_membership['attributes']['active'] ?? false);
            if ($is_active) {
                return true;
            }
        }
    } catch (Throwable $e) {
        OrgManagement\Helpers\Helper::log_error('[OrgMan] Bulk upload duplicate check failed: ' . $e->getMessage(), [
            'membership_uuid' => $membership_uuid,
            'email' => $email,
        ]);
    }

    return false;
};

$file = fopen((string) $_FILES['bulk_file']['tmp_name'], 'r');
if ($file === false) {
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('Unable to read the uploaded CSV file.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

$header_row = fgetcsv($file, 0, ',');
if (!is_array($header_row) || empty($header_row)) {
    fclose($file);
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('CSV header row is missing or invalid.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

$column_idx = $resolve_header_index($header_row);
if ($column_idx['first_name'] < 0 || $column_idx['last_name'] < 0 || $column_idx['email'] < 0) {
    fclose($file);
    status_header(200);
    OrgManagement\Helpers\DatastarSSE::renderError(
        __('CSV must include First Name, Last Name, and Email Address columns.', 'wicket-acc'),
        $message_target,
        ['membersLoading' => false, 'bulkUploadSubmitting' => false]
    );

    return;
}

$permissions_field_config = $orgman_config['member_addition_form']['fields']['permissions'] ?? [];
$allowed_roles = is_array($permissions_field_config['allowed_roles'] ?? null)
    ? $permissions_field_config['allowed_roles']
    : [];
$excluded_roles = is_array($permissions_field_config['excluded_roles'] ?? null)
    ? $permissions_field_config['excluded_roles']
    : [];

$processed = 0;
$added = 0;
$skipped = 0;
$failed = 0;
$row_limit = 1000;
$row_num = 1;
$error_snippets = [];

while (($row = fgetcsv($file, 0, ',')) !== false) {
    $row_num++;
    if (!is_array($row)) {
        continue;
    }

    if ($processed >= $row_limit) {
        break;
    }

    $first_name = sanitize_text_field((string) ($row[$column_idx['first_name']] ?? ''));
    $last_name = sanitize_text_field((string) ($row[$column_idx['last_name']] ?? ''));
    $email = sanitize_email((string) ($row[$column_idx['email']] ?? ''));
    $roles_raw = $column_idx['roles'] >= 0 ? (string) ($row[$column_idx['roles']] ?? '') : '';

    if ($first_name === '' && $last_name === '' && $email === '') {
        continue;
    }

    $processed++;

    if ($email === '' || !is_email($email) || $first_name === '' || $last_name === '') {
        $failed++;
        if (count($error_snippets) < 5) {
            $error_snippets[] = sprintf(
                __('Row %d skipped: missing required name/email fields.', 'wicket-acc'),
                $row_num
            );
        }
        continue;
    }

    if ($roster_mode !== 'groups' && $active_membership_exists($membership_uuid, $email)) {
        $skipped++;
        continue;
    }

    $roles = [];
    if ($roles_raw !== '') {
        $roles = preg_split('/[|,;]+/', $roles_raw) ?: [];
        $roles = array_map(static function ($role): string {
            return sanitize_text_field(trim((string) $role));
        }, $roles);
        $roles = array_values(array_filter($roles, static function ($role): bool {
            return $role !== '';
        }));
        $roles = OrgManagement\Helpers\PermissionHelper::filter_role_submission(
            $roles,
            $allowed_roles,
            $excluded_roles
        );
    }

    $member_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
    ];

    $context = [
        'roles' => $roles,
        'membership_uuid' => $membership_uuid,
    ];

    if ($roster_mode === 'groups') {
        $default_group_role = sanitize_key((string) ((\OrgManagement\Config\get_config()['groups']['member_role'] ?? 'member')));
        $roster_roles = $group_service instanceof GroupService
            ? $group_service->get_roster_roles()
            : [$default_group_role];
        $normalized_roles = array_values(array_filter(array_map(static function ($role): string {
            return sanitize_key((string) $role);
        }, $roles)));

        $row_role = $default_group_role;
        foreach ($normalized_roles as $candidate_role) {
            if (in_array($candidate_role, $roster_roles, true)) {
                $row_role = $candidate_role;
                break;
            }
        }

        $context['group_uuid'] = $group_uuid;
        $context['role'] = $row_role;
    }

    $result = $member_service->add_member($org_uuid, $member_data, $context);
    if (is_wp_error($result)) {
        $failed++;
        if (count($error_snippets) < 5) {
            $error_snippets[] = sprintf(
                __('Row %1$d failed (%2$s): %3$s', 'wicket-acc'),
                $row_num,
                esc_html($email),
                esc_html($result->get_error_message())
            );
        }
        continue;
    }

    $added++;
}
fclose($file);

if ($added > 0 && $membership_uuid !== '') {
    $orgman_instance = OrgManagement\OrgMan::get_instance();
    $orgman_instance->clear_members_cache($membership_uuid);
}

$summary = sprintf(
    __('Processed %1$d row(s): %2$d added, %3$d skipped, %4$d failed.', 'wicket-acc'),
    $processed,
    $added,
    $skipped,
    $failed
);

if (!empty($error_snippets)) {
    $summary .= '<br><br>' . implode('<br>', array_map('wp_kses_post', $error_snippets));
}

status_header(200);
if ($added > 0) {
    OrgManagement\Helpers\DatastarSSE::renderSuccess($summary, $message_target, [
        'membersLoading' => false,
        'bulkUploadSubmitting' => false,
    ], 'bulk-upload-countdown');
} else {
    OrgManagement\Helpers\DatastarSSE::renderError($summary, $message_target, [
        'membersLoading' => false,
        'bulkUploadSubmitting' => false,
    ]);
}

return;
