<?php

declare(strict_types=1);

namespace OrgManagement\Templates;

\Wicket()->log()->info('[OrgMan] member-details.php script execution started', [
    'get' => $_GET,
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
]);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;
use starfederation\datastar\ServerSentEventGenerator;

// Ensure this file is not accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lazy loading endpoint for member card cosmetic details.
 * Returns Datastar SSE fragments.
 */
$person_uuid = isset($_GET['person_uuid']) ? sanitize_text_field($_GET['person_uuid']) : '';
$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field($_GET['org_uuid']) : '';
$membership_uuid = isset($_GET['membership_uuid']) ? sanitize_text_field($_GET['membership_uuid']) : '';

\OrgManagement\Helpers\Helper::log_debug('[OrgMan] member-details endpoint hit', [
    'person_uuid'     => $person_uuid,
    'org_uuid'        => $org_uuid,
    'membership_uuid' => $membership_uuid,
]);

if (empty($person_uuid) || empty($org_uuid)) {
    exit;
}

$config_service = new ConfigService();
$member_service = new MemberService($config_service);

// Fetch the member with full details (lazy = false)
// We use a small cache for this request since multiple cards might intersect at once
$cache_key = 'orgman_lazy_details_' . md5($person_uuid . $org_uuid . $membership_uuid);
$member = get_transient($cache_key);

if (false === $member) {
    $result = $member_service->getMembers(
        $membership_uuid,
        $org_uuid,
        [
            'page' => 1,
            'size' => 1,
            'query' => $person_uuid, // Service supports querying by UUID
        ],
        false // Full load
    );

    // Find our specific member in the result
    $member = null;
    if (!empty($result['members'])) {
        foreach ($result['members'] as $m) {
            if (($m['person_uuid'] ?? '') === $person_uuid) {
                $member = $m;
                break;
            }
        }
    }

    if ($member) {
        set_transient($cache_key, $member, 1 * HOUR_IN_SECONDS);
    }
}

// Initialize Datastar SSE Generator
$generator = new ServerSentEventGenerator();
$person_uuid_no_dashes = str_replace('-', '', $person_uuid);

// If the member was filtered out by the full load (e.g. relationship filters), remove the card
if (!$member) {
    // Delete the entire card container
    $generator->deleteFragments('#member-card-' . $person_uuid_no_dashes);
    exit;
}

// Mark as lazy loaded for the template logic
$member['lazy_loaded'] = true;

// Shared variables for the partials
$member_list_config = $config_service->getMemberListConfig();
$show_account_status = (bool) ($member_list_config['show_account_status'] ?? true);
$show_unconfirmed_label = (bool) ($member_list_config['show_unconfirmed_label'] ?? true);
$unconfirmed_label = (string) ($member_list_config['unconfirmed_label'] ?? __('Unconfirmed', 'wicket-acc'));
$confirmed_tooltip = __('Confirmed Wicket Account', 'wicket-acc');
$unconfirmed_tooltip = __('Unconfirmed Wicket Account', 'wicket-acc');
$member_email = $member['email'] ?? '';

// Fragment 1: Update Status Indicator
ob_start();
?>
<div id="member-status-<?php echo esc_attr($person_uuid_no_dashes); ?>" class="wt_inline-flex wt_items-center" data-merge="morph">
    <?php if ($show_account_status) : ?>
        <?php if (!empty($member['is_confirmed'])) : ?>
            <span class="wt_text-content" title="<?php echo esc_attr($confirmed_tooltip); ?>">
                <span class="wt_inline-block wt_w-2 wt_h-2 wt_rounded-full wt_bg-green-500" aria-hidden="true"></span>
            </span>
        <?php else : ?>
            <span class="wt_text-content" title="<?php echo esc_attr($unconfirmed_tooltip); ?>">
                <span class="wt_inline-block wt_w-2 wt_h-2 wt_rounded-full wt_bg-gray-400" aria-hidden="true"></span>
            </span>
            <?php if ($show_unconfirmed_label && $unconfirmed_label !== '') : ?>
                <span class="wt_text-warning wt_whitespace-nowrap wt_ml-1 wt_text-2xs" title="<?php echo esc_attr($unconfirmed_tooltip); ?>">
                    <?php echo esc_html($unconfirmed_label); ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$status_html = ob_get_clean();
$generator->mergeFragments($status_html);

// Fragment 2: Update Details Block (Roles, Relationships, Email)
$role_display_map = (array) ($member_list_config['display_roles']['labels'] ?? []);
$current_roles = !empty($member['current_roles']) ? $member['current_roles'] : ($member['roles'] ?? []);
$formatted_roles = array_map(static function ($role) use ($role_display_map) {
    if (isset($role_display_map[$role])) {
        return $role_display_map[$role];
    }

    return ucwords(str_replace('_', ' ', (string) $role));
}, is_array($current_roles) ? $current_roles : []);
$roles_text = !empty($formatted_roles) ? implode(', ', $formatted_roles) : '—';

ob_start();
?>
<div id="member-details-<?php echo esc_attr($person_uuid_no_dashes); ?>" class="wt_flex wt_flex-col wt_gap-2" data-merge="morph">
    <?php
    // Check if relationship type should be hidden
    if (!empty($member['relationship_names']) && !\OrgManagement\Helpers\Helper::should_hide_relationship_type()) :
        ?>
        <div class="wt_flex wt_items-center wt_gap-2">
            <span class="wt_text-content"><?php echo esc_html($member['relationship_names']); ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($member_email)) : ?>
        <div class="wt_flex wt_items-center wt_gap-2">
            <a href="mailto:<?php echo esc_attr($member_email); ?>" class="wt_text-sm wt_text-interactive wt_hover_underline">
                <?php echo esc_html($member_email); ?>
            </a>
        </div>
    <?php endif; ?>
    <?php if (\OrgManagement\Helpers\Helper::should_show_member_roles()) : ?>
        <div class="wt_flex wt_items-baseline wt_gap-2 wt_text-sm">
            <strong><?php esc_html_e('Roles:', 'wicket-acc'); ?></strong>
            <span class="wt_text-content"><?php echo esc_html($roles_text); ?></span>
        </div>
    <?php endif; ?>
</div>
<?php
$details_html = ob_get_clean();
$generator->mergeFragments($details_html);
