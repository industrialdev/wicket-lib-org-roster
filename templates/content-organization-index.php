<?php

/**
 * Content-only template for Organization Management Index.
 * This template contains only the OrgMan content to be injected after the_content.
 *
 * @package OrgManagement
 */

namespace OrgManagement\Templates;

if (! is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

// Roster mode selection
$config_service = new \OrgManagement\Services\ConfigService();
$roster_mode = $config_service->get_roster_mode();

// Normalize query param: prefer org_uuid; redirect from org_id => org_uuid
$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field($_GET['org_uuid']) : '';
$org_id_fallback = isset($_GET['org_id']) ? sanitize_text_field($_GET['org_id']) : '';
if (empty($org_uuid) && !empty($org_id_fallback)) {
    $current_url = home_url(add_query_arg([]));
    // Preserve existing params but replace org_id with org_uuid
    $params = $_GET;
    unset($params['org_id']);
    $params['org_uuid'] = $org_id_fallback;
    wp_redirect(add_query_arg(array_map('sanitize_text_field', $params), $current_url));
    exit;
}

?>

<div id="org-management-index-app" class="org-management-app wicket-orgman wt_w-full wt_mt-6">
    <h1 class="wt_text-2xl wt_font-bold wt_mb-4"><?php esc_html_e('Manage Organizations', 'wicket-acc'); ?></h1>

    <?php
    if ($roster_mode === 'groups') {
        include dirname(__DIR__) . '/templates-partials/organization-list.php';
    } elseif (!empty($org_uuid)) {
        include dirname(__DIR__) . '/templates-partials/organization-details.php';
    } else {
        include dirname(__DIR__) . '/templates-partials/organization-list.php';
    }
    ?>
</div>
