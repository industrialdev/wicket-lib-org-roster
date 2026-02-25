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

$user_uuid = wp_get_current_user()->user_login;

// Services
$membership_service = new \OrgManagement\Services\MembershipService();
$config_service = new \OrgManagement\Services\ConfigService();
$roster_mode = $config_service->get_roster_mode();
$group_uuid = isset($_GET['group_uuid']) ? sanitize_text_field($_GET['group_uuid']) : '';

// Fetch organization basic info
$org_name = '';
$group_name = '';
if ($roster_mode === 'groups' && $group_uuid && function_exists('wicket_get_group')) {
    $group = wicket_get_group($group_uuid);
    if (is_array($group) && isset($group['data']['attributes'])) {
        $attrs = $group['data']['attributes'];
        $group_name = $attrs['name'] ?? $attrs['name_en'] ?? $attrs['name_fr'] ?? '';
    }
    if (empty($org_uuid) && is_array($group)) {
        $org_uuid = (string) ($group['data']['relationships']['organization']['data']['id'] ?? '');
    }
}

if (!$org_uuid && $roster_mode !== 'groups') {
    echo '<div class="notice">' . esc_html__('Organization not specified.', 'wicket-acc') . '</div>';

    return;
}
if ($org_uuid !== '' && function_exists('wicket_get_organization')) {
    $org = wicket_get_organization($org_uuid);
    if (is_array($org) && isset($org['data']['attributes'])) {
        $attrs = $org['data']['attributes'];
        $org_name = $attrs['legal_name'] ?? ($attrs['legal_name_en'] ?? ($attrs['name'] ?? ''));
    }
}
if (!$org_name) {
    $org_name = esc_html__('Organization', 'wicket-acc');
}

// Membership UUID and data
$membership_uuid = $org_uuid ? $membership_service->getMembershipForOrganization($org_uuid) : '';
$membership_data = $membership_uuid ? $membership_service->getOrgMembershipData($membership_uuid) : null;

// Extract membership summary info
$membership_name = '';
$owner_name = '';
$renewal_date = '';
$seats_label = '';

if ($membership_data) {
    $membership_name = $membership_service->getMembershipTierName($membership_data);
    $owner_id = $membership_data['data']['relationships']['owner']['data']['id'] ?? '';
    $resolve_person_name = static function ($person): string {
        if (empty($person)) {
            return '';
        }

        $attributes = [];
        if (is_array($person)) {
            if (isset($person['data']['attributes']) && is_array($person['data']['attributes'])) {
                $attributes = $person['data']['attributes'];
            } elseif (isset($person['attributes']) && is_array($person['attributes'])) {
                $attributes = $person['attributes'];
            } else {
                $attributes = $person;
            }
        } elseif (is_object($person)) {
            $attributes = isset($person->attributes)
                ? (is_array($person->attributes) ? $person->attributes : (array) $person->attributes)
                : (array) $person;
        }

        $first = trim((string) ($attributes['given_name'] ?? $attributes['first_name'] ?? ''));
        $last = trim((string) ($attributes['family_name'] ?? $attributes['last_name'] ?? ''));
        $full = trim($first . ' ' . $last);

        if ($full !== '') {
            return $full;
        }

        $fallback = trim((string) ($attributes['full_name'] ?? $attributes['name'] ?? ''));

        return $fallback;
    };

    if ($owner_id !== '') {
        if (function_exists('wicket_get_person_by_id')) {
            $owner = wicket_get_person_by_id($owner_id);
            $owner_name = $resolve_person_name($owner);
        }

        if ($owner_name === '' && !empty($membership_data['included']) && is_array($membership_data['included'])) {
            foreach ($membership_data['included'] as $included_item) {
                if (($included_item['type'] ?? '') !== 'people') {
                    continue;
                }
                if ((string) ($included_item['id'] ?? '') !== (string) $owner_id) {
                    continue;
                }

                $owner_name = $resolve_person_name($included_item);
                if ($owner_name !== '') {
                    break;
                }
            }
        }
    }

    $date_candidates = [
        $membership_data['data']['attributes']['ends_at'] ?? '',
        $membership_data['data']['attributes']['end_at'] ?? '',
        $membership_data['data']['attributes']['expires_at'] ?? '',
        $membership_data['data']['attributes']['renewal_date'] ?? '',
        $membership_data['data']['attributes']['next_renewal_at'] ?? '',
    ];
    if (!empty($membership_data['included']) && is_array($membership_data['included'])) {
        foreach ($membership_data['included'] as $included_item) {
            if (($included_item['type'] ?? '') !== 'memberships') {
                continue;
            }
            $date_candidates[] = $included_item['attributes']['ends_at'] ?? '';
            $date_candidates[] = $included_item['attributes']['expires_at'] ?? '';
            $date_candidates[] = $included_item['attributes']['renewal_date'] ?? '';
        }
    }

    $ends_at = '';
    foreach ($date_candidates as $candidate_date) {
        $candidate_date = is_string($candidate_date) ? trim($candidate_date) : '';
        if ($candidate_date !== '') {
            $ends_at = $candidate_date;
            break;
        }
    }

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
    $max = $membership_service->getEffectiveMaxAssignments($membership_data);
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
            <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html__('Membership Owner-', 'wicket-acc') . ' ' . esc_html($owner_name !== '' ? $owner_name : '—'); ?></p>
            <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html__('Renewal Date-', 'wicket-acc') . ' ' . esc_html($renewal_date !== '' ? $renewal_date : '—'); ?></p>
            <?php if ($seats_label): ?>
                <p class="org-details__summary-item wt_leading-normal wt_text-content mb-1"><?php echo esc_html($seats_label); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="org-details__actions wt_flex wt_items-center wt_justify-evenly wt_gap-8 wt_mt-4">
        <?php
        // Check user permissions for this organization
        if ($roster_mode === 'groups' && $group_uuid !== '') {
            $group_service = new \OrgManagement\Services\GroupService();
            $group_access = $group_service->can_manage_group($group_uuid, (string) $user_uuid);
            $can_edit_org = !empty($group_access['allowed']);
            $is_membership_manager = !empty($group_access['allowed']);
            $can_bulk_upload = !empty($group_access['allowed']);
        } else {
            $can_edit_org = \OrgManagement\Helpers\PermissionHelper::can_edit_organization($org_uuid);
            $is_membership_manager = \OrgManagement\Helpers\PermissionHelper::is_membership_manager($org_uuid);
            $can_bulk_upload = \OrgManagement\Helpers\PermissionHelper::can_add_members($org_uuid);
        }

// Get WPML-aware URLs for my-account pages
$profile_url = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-profile', '/my-account/organization-profile/');
$members_url = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-members', '/my-account/organization-members/');
$members_bulk_url = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-members-bulk', '/my-account/organization-members-bulk/');
$profile_params = [];
$members_params = [];
if ($org_uuid !== '') {
    $profile_params['org_uuid'] = $org_uuid;
    $members_params['org_uuid'] = $org_uuid;
}
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

        <?php
        $member_list_config = \OrgManagement\Config\get_config()['ui']['member_list'] ?? [];
$show_bulk_upload = (bool) ($member_list_config['show_bulk_upload'] ?? false);
if ($show_bulk_upload && $can_bulk_upload):
    ?>
            <a href="<?php echo esc_url(add_query_arg($members_params, $members_bulk_url)); ?>"
                class="org-details__action-link wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4"><?php esc_html_e('Bulk Upload', 'wicket-acc'); ?></a>
        <?php endif; ?>
    </div>
    <div class="org-details__divider wt_border-b wt_border-primary-600 wt_mt-1"></div>
</div>
