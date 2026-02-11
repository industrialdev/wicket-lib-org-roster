<?php

/**
 * Organization Details Partial Template.
 *
 * Renders a single-organization summary view when org_id is present.
 */

namespace OrgManagement\Templates;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
// Basic permission check.
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this content.');
}

$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field($_GET['org_uuid']) : '';
// Fallback for older links: org_id
if (empty($org_uuid) && isset($_GET['org_id'])) {
    $org_uuid = sanitize_text_field($_GET['org_id']);
}
if (!$org_uuid) {
    echo '<div class="notice">' . esc_html__('Organization not specified.', 'wicket-acc') . '</div>';

    return;
}

$user_uuid = wp_get_current_user()->user_login;

// Services
$membership_service = new \OrgManagement\Services\MembershipService();
$config_service = new \OrgManagement\Services\ConfigService();
$roster_mode = $config_service->get_roster_mode();

// Fetch organization basic info
$org_name = '';
$group_name = '';
if (function_exists('wicket_get_organization')) {
    $org = wicket_get_organization($org_uuid);
    if (is_array($org) && isset($org['data']['attributes'])) {
        $attrs = $org['data']['attributes'];
        $org_name = $attrs['legal_name'] ?? ($attrs['legal_name_en'] ?? ($attrs['name'] ?? ''));
    }
}
if (!$org_name) {
    $org_name = esc_html__('Organization', 'wicket-acc');
}

if ($roster_mode === 'groups') {
    $group_uuid = isset($_GET['group_uuid']) ? sanitize_text_field($_GET['group_uuid']) : '';
    if ($group_uuid && function_exists('wicket_get_group')) {
        $group = wicket_get_group($group_uuid);
        if (is_array($group) && isset($group['data']['attributes'])) {
            $attrs = $group['data']['attributes'];
            $group_name = $attrs['name'] ?? $attrs['name_en'] ?? $attrs['name_fr'] ?? '';
        }
    }
}

// Membership UUID and data
$membership_uuid = $membership_service->getMembershipForOrganization($org_uuid);
$membership_data = $membership_uuid ? $membership_service->getOrgMembershipData($membership_uuid) : null;

// Extract membership summary info
$membership_name = '';
$owner_name = '';
$renewal_date = '';
$seats_label = '';

if ($membership_data) {
    if (isset($membership_data['included']) && is_array($membership_data['included'])) {
        foreach ($membership_data['included'] as $included) {
            if (($included['type'] ?? '') === 'memberships') {
                $membership_name = $included['attributes']['name'] ?? $included['attributes']['name_en'] ?? '';
            }
        }
    }
    $owner_id = $membership_data['data']['relationships']['owner']['data']['id'] ?? '';
    if ($owner_id && function_exists('wicket_get_person_by_id')) {
        $owner = wicket_get_person_by_id($owner_id);
        $gn = '';
        $fn = '';
        if (is_array($owner) && isset($owner['attributes'])) {
            $gn = $owner['attributes']['given_name'] ?? '';
            $fn = $owner['attributes']['family_name'] ?? '';
        } elseif (is_object($owner)) {
            // Prefer direct properties on entity
            if (isset($owner->given_name)) {
                $gn = (string) $owner->given_name;
            }
            if (isset($owner->family_name)) {
                $fn = (string) $owner->family_name;
            }
            // Fallback: attributes bag as object/array
            if ((empty($gn) || empty($fn)) && isset($owner->attributes)) {
                $attrs = is_array($owner->attributes) ? $owner->attributes : (array) $owner->attributes;
                $gn = $gn ?: ($attrs['given_name'] ?? '');
                $fn = $fn ?: ($attrs['family_name'] ?? '');
            }
        }
        $owner_name = trim(trim($gn) . ' ' . trim($fn));
    }
    $ends_at = $membership_data['data']['attributes']['ends_at'] ?? '';
    if ($ends_at) {
        try {
            $dt = new \DateTime($ends_at);
            $renewal_date = $dt->format('F j, Y');
        } catch (\Throwable $e) {
            $renewal_date = '';
        }
    }
    // Seats (if available)
    $active = $membership_data['data']['attributes']['active_assignments_count'] ?? null;
    $max = $membership_data['data']['attributes']['max_assignments'] ?? null;
    if ($active !== null || $max !== null) {
        $max_label = $max !== null ? $max : esc_html__('Unlimited', 'wicket-acc');
        $seats_label = sprintf('%s %s / %s', esc_html__('Seats:', 'wicket-acc'), (string) $active, (string) $max_label);
    }
}
?>
<div id="organization-details-container" class="org-details wt_flex wt_flex-col wt_gap-3">
    <div class="org-details__summary-card wt_rounded-card-accent wt_p-4 wt_bg-summary-card">
        <h2 class="org-details__title wt_text-heading-sm wt_mb-2 wt_text-heading-color wt_font-extrabold"><?php echo esc_html($org_name); ?></h2>
        <?php if ($group_name): ?>
            <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1">
                <?php echo esc_html($group_name); ?>
            </p>
        <?php endif; ?>
        <div class="org-details__summary-list wt_flex wt_flex-col wt_gap-0">
            <p class="org-details__summary-heading wt_font-bold wt_mb-1"><?php esc_html_e('Summary', 'wicket-acc'); ?></p>
            <?php if ($membership_name): ?>
                <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html__('Membership Tier-', 'wicket-acc') . ' ' . esc_html($membership_name); ?></p>
            <?php endif; ?>
            <?php if ($owner_name): ?>
                <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html__('Membership Owner-', 'wicket-acc') . ' ' . esc_html($owner_name); ?></p>
            <?php endif; ?>
            <?php if ($renewal_date): ?>
                <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html__('Renewal Date-', 'wicket-acc') . ' ' . esc_html($renewal_date); ?></p>
            <?php endif; ?>
            <?php if ($seats_label): ?>
                <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html($seats_label); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="org-details__actions wt_flex wt_items-center wt_justify-evenly wt_gap-8 wt_mt-4">
        <?php
        // Check user permissions for this organization
        $can_edit_org = \OrgManagement\Helpers\PermissionHelper::can_edit_organization($org_uuid);
$is_membership_manager = \OrgManagement\Helpers\PermissionHelper::is_membership_manager($org_uuid);

// Get WPML-aware URLs for my-account pages
$profile_url = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-profile', '/my-account/organization-profile/');
$members_url = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-members', '/my-account/organization-members/');
$group_uuid = isset($_GET['group_uuid']) ? sanitize_text_field($_GET['group_uuid']) : '';
$profile_params = ['org_uuid' => $org_uuid];
$members_params = ['org_uuid' => $org_uuid];
if ($roster_mode === 'groups' && $group_uuid !== '') {
    $profile_params['group_uuid'] = $group_uuid;
    $members_params['group_uuid'] = $group_uuid;
}
?>

        <?php if ($can_edit_org): ?>
            <a href="<?php echo esc_url(add_query_arg($profile_params, $profile_url)); ?>"
                class="org-details__action-link wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4"><?php esc_html_e('Org Profile', 'wicket-acc'); ?></a>
        <?php endif; ?>

        <?php if ($is_membership_manager): ?>
            <a href="<?php echo esc_url(add_query_arg($members_params, $members_url)); ?>"
                class="org-details__action-link wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4"><?php esc_html_e('Manage Members', 'wicket-acc'); ?></a>
        <?php endif; ?>
    </div>
    <div class="org-details__divider wt_border-b wt_border-primary-600 wt_mt-1"></div>
</div>
