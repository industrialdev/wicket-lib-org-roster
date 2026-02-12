<?php

/**
 * Organization List Partial Template.
 *
 * Displays a filtered list of organizations where the current user has active memberships.
 * Only shows organizations with non-expired membership end dates.
 */

namespace OrgManagement\Templates;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Basic permission check.
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this content.');
} ?>

<?php
$config_service = new \OrgManagement\Services\ConfigService();
$roster_mode = $config_service->get_roster_mode();

// Get current user identifier
$user_uuid = wp_get_current_user()->user_login;

// Get organizations data from template helper or fallback to service
if (!isset($organizations)) {
    $org_service = new \OrgManagement\Services\OrganizationService();
    $organizations = $org_service->get_user_organizations($user_uuid);
}

// Handle connection errors
if (isset($error) && is_array($error)) {
    ?>

    <div id="organization-list-container" class="wt_bg-red-50 wt_border wt_border-red-200 wt_rounded-md wt_shadow-sm wt_p-6">
        <div class="wt_flex wt_items-center">
            <svg class="wt_w-5 wt_h-5 wt_text-red-500 wt_mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <h3 class="wt_text-lg wt_font-medium wt_text-red-800"><?php esc_html_e('Connection Error', 'wicket-acc'); ?></h3>
        </div>
        <p class="wt_mt-2 wt_text-red-700">
            <?php
                if (isset($error['message'])) {
                    echo esc_html($error['message']);
                } else {
                    esc_html_e('Unable to connect to the organization service. Please try again later.', 'wicket-acc');
                }
    ?>
        </p>
        <button
            onclick="location.reload()"
            class="wt_mt-4 wt_px-4 wt_py-2 wt_bg-red-600 wt_text-white wt_rounded-md wt_hover_bg-red-700 wt_focus_outline-hidden wt_focus_ring-2 wt_focus_ring-red-500">
            <?php esc_html_e('Try Again', 'wicket-acc'); ?>
        </button>
    </div>
<?php
    return;
}

// Handle empty organizations data
if (empty($organizations) || !is_array($organizations)) {
    ?>
    <div id="organization-list-container">
        <p><?php esc_html_e('You currently have no organizations to manage members for.', 'wicket-acc'); ?></p>
    </div>
<?php
        return;
}

// Apply active membership filtering using the service
$org_service = new \OrgManagement\Services\OrganizationService();
$organizations = $org_service->filter_active_organizations($organizations, $user_uuid);

// Map roster-management groups per organization for card display.
$logger = wc_get_logger();
$groups_by_org = [];
$groups_by_org_tagged = [];
$group_service = new \OrgManagement\Services\GroupService();
$group_page = 1;
$group_total_pages = 1;
$logger->info('[OrgRoster] Organization list group mapping start', [
    'source' => 'wicket-orgroster',
    'user_uuid' => $user_uuid,
    'page_size' => $group_service->get_group_list_page_size(),
]);
do {
    $logger->debug('[OrgRoster] Fetching manageable groups page', [
        'source' => 'wicket-orgroster',
        'user_uuid' => $user_uuid,
        'page' => $group_page,
    ]);
    $groups_result = $group_service->get_manageable_groups($user_uuid, [
        'page' => $group_page,
        'size' => $group_service->get_group_list_page_size(),
        'query' => '',
    ]);

    $logger->debug('[OrgRoster] Manageable groups response', [
        'source' => 'wicket-orgroster',
        'page' => $group_page,
        'has_data' => is_array($groups_result),
        'data_count' => is_array($groups_result) ? count($groups_result['data'] ?? []) : 0,
        'meta' => is_array($groups_result) ? ($groups_result['meta']['page'] ?? []) : null,
    ]);

    $group_items = is_array($groups_result) ? ($groups_result['data'] ?? []) : [];
    foreach ($group_items as $group_item) {
        $org_uuid = $group_item['org_uuid'] ?? '';
        if ($org_uuid === '') {
            $logger->debug('[OrgRoster] Skipping group item missing org_uuid', [
                'source' => 'wicket-orgroster',
                'group_item' => $group_item,
            ]);
            continue;
        }
        $group = $group_item['group'] ?? [];
        $group_id = $group['id'] ?? '';
        $attrs = is_array($group) ? ($group['attributes'] ?? []) : [];
        $group_name = $attrs['name'] ?? $attrs['name_en'] ?? $attrs['name_fr'] ?? '';
        $group_type = $attrs['type'] ?? '';
        $group_tags = is_array($attrs) ? ($attrs['tags'] ?? null) : null;
        if ($group_name === '' && $group_type === '') {
            $logger->debug('[OrgRoster] Skipping group item missing name/type', [
                'source' => 'wicket-orgroster',
                'org_uuid' => $org_uuid,
                'group_id' => $group['id'] ?? '',
            ]);
            continue;
        }
        if (!isset($groups_by_org[$org_uuid])) {
            $groups_by_org[$org_uuid] = [];
        }
        $groups_by_org[$org_uuid][] = [
            'id' => $group_id,
            'name' => $group_name,
            'type' => $group_type,
            'tags' => $group_tags,
        ];
        $logger->debug('[OrgRoster] Group mapped to org', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_uuid,
            'group_id' => $group['id'] ?? '',
            'group_name' => $group_name,
            'group_type' => $group_type,
        ]);
    }

    $group_meta = $groups_result['meta']['page'] ?? [];
    $group_total_pages = (int) ($group_meta['total_pages'] ?? 1);
    $group_total_pages = max(1, $group_total_pages);
    $group_page++;
} while ($group_page <= $group_total_pages);
$logger->info('[OrgRoster] Organization list group mapping complete', [
    'source' => 'wicket-orgroster',
    'org_count' => count($organizations),
    'group_org_count' => count($groups_by_org),
    'group_total_pages' => $group_total_pages,
]);

// Also get membership tier information for each organization.
// Keep legacy single-tier behavior unless membership_cycle strategy is active.
$membership_tiers = [];
$membership_entries_by_org = [];
if (function_exists('wicket_get_current_person_memberships')) {
    $all_memberships = wicket_get_current_person_memberships();
    if ($all_memberships && isset($all_memberships['included'])) {
        foreach ($all_memberships['included'] as $included) {
            if (
                $included['type'] === 'organization_memberships'
                && isset($included['relationships']['organization']['data']['id'])
                && isset($included['relationships']['membership']['data']['id'])
            ) {

                $org_id = $included['relationships']['organization']['data']['id'];
                $membership_id = $included['relationships']['membership']['data']['id'];
                $is_active = $included['attributes']['active'] ?? false;

                // Include if membership is active OR if this org has roles (regardless of membership status)
                $should_include = false;
                if ($is_active) {
                    $should_include = true; // Active membership based on API field
                } else {
                    // Check if this organization has roles even if membership is inactive
                    foreach ($all_memberships['included'] as $org_item) {
                        if (
                            $org_item['type'] === 'organizations'
                            && $org_item['id'] === $org_id
                            && isset($org_item['relationships']['roles']['data'])
                            && !empty($org_item['relationships']['roles']['data'])
                        ) {
                            $should_include = true;
                            break;
                        }
                    }
                }

                if ($should_include) {
                    // Find the membership name from the included memberships
                    foreach ($all_memberships['included'] as $membership) {
                        if ($membership['type'] === 'memberships' && $membership['id'] === $membership_id) {
                            $membership_name = $membership['attributes']['name'] ?? $membership['attributes']['name_en'] ?? 'Active Membership';

                            // Add "(Inactive)" suffix if membership is not active
                            if (!$is_active) {
                                $membership_name .= ' (Inactive)';
                            }

                            if ($roster_mode === 'membership_cycle') {
                                if (!isset($membership_tiers[$org_id]) || !is_array($membership_tiers[$org_id])) {
                                    $membership_tiers[$org_id] = [];
                                }
                                $membership_tiers[$org_id][] = $membership_name;
                                if (!isset($membership_entries_by_org[$org_id]) || !is_array($membership_entries_by_org[$org_id])) {
                                    $membership_entries_by_org[$org_id] = [];
                                }
                                $membership_entries_by_org[$org_id][] = [
                                    'membership_uuid' => (string) ($included['id'] ?? ''),
                                    'membership_name' => $membership_name,
                                    'is_active' => (bool) $is_active,
                                ];
                            } else {
                                $membership_tiers[$org_id] = $membership_name;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
}

// Handle case where no active memberships found
if (empty($organizations)) {
    ?>
    <div id="organization-list-container">
        <p><?php esc_html_e('You currently have no active organization memberships.', 'wicket-acc'); ?></p>
    </div>
<?php
        return;
}
?>

<div id="organization-list-container">
    <?php
    // Display organization count
    $count = count($organizations);
echo "<p class='mb-2'>" . __('Organizations Found:', 'wicket-acc') . ' ' . $count . '</p>';

// Start organization list
echo '<div class="wt_w-full wt_flex wt_flex-col wt_gap-4" role="list">';
// Initialize membership service once for the loop
$membership_service = new \OrgManagement\Services\MembershipService();
foreach ($organizations as $org) :
    $org_id = $org['id'];
    $org_name = $org['org_name'] ?? __('Unknown', 'wicket-acc');
    $group_details = $groups_by_org[$org_id] ?? [];
    try {
        $tag_name = $group_service->get_roster_tag_name();
        $groups_response = wicket_api_client()->get('/groups', [
            'query' => [
                'page' => [
                    'number' => 1,
                    'size' => 50,
                ],
                'filter' => [
                    'organization_uuid_eq' => $org_id,
                    'tags_name_eq' => $tag_name,
                ],
                'sort' => 'name_en',
            ],
        ]);
        $groups_data = is_array($groups_response) ? ($groups_response['data'] ?? []) : [];
        foreach ($groups_data as $group_item) {
            $attrs = is_array($group_item) ? ($group_item['attributes'] ?? []) : [];
            $group_name = $attrs['name'] ?? $attrs['name_en'] ?? $attrs['name_fr'] ?? '';
            $group_type = $attrs['type'] ?? '';
            $group_tags = is_array($attrs) ? ($attrs['tags'] ?? null) : null;
            if ($group_name === '' && $group_type === '') {
                continue;
            }
            if (!isset($groups_by_org_tagged[$org_id])) {
                $groups_by_org_tagged[$org_id] = [];
            }
            $groups_by_org_tagged[$org_id][] = [
                'id' => $group_item['id'] ?? '',
                'name' => $group_name,
                'type' => $group_type,
                'tags' => $group_tags,
            ];
        }
        $logger->debug('[OrgRoster] Org groups tags via /groups', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_id,
            'tag' => $tag_name,
            'count' => count($groups_data),
            'group_ids' => array_map(static function ($group_item) {
                return $group_item['id'] ?? '';
            }, $groups_data),
            'group_tags' => array_map(static function ($group_item) {
                $attrs = is_array($group_item) ? ($group_item['attributes'] ?? []) : [];

                return $attrs['tags'] ?? null;
            }, $groups_data),
        ]);

        $groups_response_all = wicket_api_client()->get('/groups', [
            'query' => [
                'page' => [
                    'number' => 1,
                    'size' => 50,
                ],
                'filter' => [
                    'organization_uuid_eq' => $org_id,
                ],
                'sort' => 'name_en',
            ],
        ]);
        $groups_all = is_array($groups_response_all) ? ($groups_response_all['data'] ?? []) : [];
        $logger->debug('[OrgRoster] Org groups tags via /groups (no tag filter)', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_id,
            'count' => count($groups_all),
            'group_ids' => array_map(static function ($group_item) {
                return $group_item['id'] ?? '';
            }, $groups_all),
            'group_tags' => array_map(static function ($group_item) {
                $attrs = is_array($group_item) ? ($group_item['attributes'] ?? []) : [];

                return $attrs['tags'] ?? null;
            }, $groups_all),
        ]);
    } catch (\Throwable $e) {
        $logger->error('[OrgRoster] Org groups tags fetch failed', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_id,
            'error' => $e->getMessage(),
        ]);
    }
    if (!empty($groups_by_org_tagged[$org_id])) {
        $tagged_index = [];
        foreach ($groups_by_org_tagged[$org_id] as $tagged_group) {
            $tagged_id = $tagged_group['id'] ?? '';
            if ($tagged_id !== '') {
                $tagged_index[$tagged_id] = $tagged_group;
            }
        }
        if (empty($group_details)) {
            $group_details = $groups_by_org_tagged[$org_id];
        } else {
            foreach ($group_details as $idx => $group_detail) {
                $detail_id = $group_detail['id'] ?? '';
                if ($detail_id === '' || empty($tagged_index[$detail_id])) {
                    continue;
                }
                $tagged = $tagged_index[$detail_id];
                if (empty($group_detail['tags']) && !empty($tagged['tags'])) {
                    $group_details[$idx]['tags'] = $tagged['tags'];
                }
                if (empty($group_detail['name']) && !empty($tagged['name'])) {
                    $group_details[$idx]['name'] = $tagged['name'];
                }
                if (empty($group_detail['type']) && !empty($tagged['type'])) {
                    $group_details[$idx]['type'] = $tagged['type'];
                }
            }
        }
    }

    // Get membership information
    $membership_uuid = $membership_service->getMembershipForOrganization($org_id);
    $membership_data = $membership_uuid ? $membership_service->getOrgMembershipData($membership_uuid) : null;

    // Extract membership names from pre-calculated tiers first (membership_cycle only),
    // then fallback to single membership data for all strategies.
    $membership_names = [];
    if (
        $roster_mode === 'membership_cycle'
        && isset($membership_tiers[$org_id])
        && is_array($membership_tiers[$org_id])
    ) {
        $membership_names = array_values(array_unique(array_filter($membership_tiers[$org_id], static function ($name) {
            return is_string($name) && $name !== '';
        })));
    } elseif (
        $roster_mode !== 'membership_cycle'
        && isset($membership_tiers[$org_id])
        && is_string($membership_tiers[$org_id])
        && $membership_tiers[$org_id] !== ''
    ) {
        $membership_names[] = $membership_tiers[$org_id];
    } elseif ($membership_data && isset($membership_data['included'])) {
        foreach ($membership_data['included'] as $included) {
            if ($included['type'] === 'memberships') {
                $membership_name = $included['attributes']['name'] ?? $included['attributes']['name_en'] ?? '';
                if ($membership_name !== '') {
                    $membership_names[] = $membership_name;
                }
                break;
            }
        }
    }
    $membership_label = implode(', ', $membership_names);

    // Build membership entries for display.
    $membership_entries = [];
    if ($roster_mode === 'membership_cycle' && isset($membership_entries_by_org[$org_id]) && is_array($membership_entries_by_org[$org_id])) {
        $seen_membership_uuids = [];
        foreach ($membership_entries_by_org[$org_id] as $entry) {
            $entry_uuid = (string) ($entry['membership_uuid'] ?? '');
            if ($entry_uuid !== '' && isset($seen_membership_uuids[$entry_uuid])) {
                continue;
            }
            if ($entry_uuid !== '') {
                $seen_membership_uuids[$entry_uuid] = true;
            }
            $membership_entries[] = [
                'membership_uuid' => $entry_uuid,
                'membership_name' => (string) ($entry['membership_name'] ?? ''),
                'is_active' => (bool) ($entry['is_active'] ?? false),
            ];
        }
    }
    if (empty($membership_entries)) {
        $membership_entries[] = [
            'membership_uuid' => (string) $membership_uuid,
            'membership_name' => (string) $membership_label,
            'is_active' => \OrgManagement\Helpers\PermissionHelper::has_active_membership($org_id),
        ];
    }

    // Get user roles for this organization using PermissionHelper
    $raw_roles = \OrgManagement\Helpers\PermissionHelper::get_user_org_roles($org_id);
    if (empty($raw_roles) && !empty($org['roles']) && is_array($org['roles'])) {
        $raw_roles = $org['roles'];
    }

    // Prepare roles for display using PermissionHelper
    $formatted_roles = \OrgManagement\Helpers\PermissionHelper::format_roles_for_display($raw_roles);

    $primary_group_uuid = '';
    if ($roster_mode === 'groups' && !empty($group_details)) {
        $primary_group_uuid = $group_details[0]['id'] ?? '';
    }
    $has_active_membership = \OrgManagement\Helpers\PermissionHelper::has_active_membership($org_id);
    $is_membership_manager = \OrgManagement\Helpers\PermissionHelper::is_membership_manager($org_id);
    $can_edit_org = \OrgManagement\Helpers\PermissionHelper::can_edit_organization($org_id);
    $has_any_roles = \OrgManagement\Helpers\PermissionHelper::has_management_roles($org_id);
    $logger->debug('[OrgRoster] Org list manage members link context', [
        'source' => 'wicket-orgroster',
        'org_uuid' => $org_id,
        'roster_mode' => $roster_mode,
        'primary_group_uuid' => $primary_group_uuid,
        'group_count' => is_array($group_details) ? count($group_details) : 0,
        'group_ids' => is_array($group_details) ? array_map(static function ($group_detail) {
            return $group_detail['id'] ?? '';
        }, $group_details) : null,
    ]);
    ?>
        <div class="wt_w-full wt_rounded-card-accent wt_p-4 wt_mb-4 wt_hover_shadow-sm wt_transition-shadow wt_bg-card wt_border wt_border-color"
            role="listitem">
            <h2 class="wt_text-heading-xs wt_mb-3">
                <a href="<?php echo esc_url(\OrgManagement\Helpers\Helper::get_my_account_page_url('organization-management', '/my-account/organization-management/') . '?org_uuid=' . urlencode($org_id)); ?>"
                    class="wt_text-content wt_hover_text-primary-600 wt_focus_outline-hidden wt_focus_ring-2 wt_focus_ring-primary-500 wt_focus_ring-offset-2 wt_decoration-none">
                    <?php echo esc_html($org_name); ?>
                </a>
            </h2>
            <div class="wt_flex wt_flex-col wt_gap-3">
                <?php foreach ($membership_entries as $entry_index => $membership_entry): ?>
                    <?php
                    $entry_membership_uuid = (string) ($membership_entry['membership_uuid'] ?? '');
                    $entry_membership_name = (string) ($membership_entry['membership_name'] ?? '');
                    $entry_is_active = (bool) ($membership_entry['is_active'] ?? false);
                    if ($roster_mode !== 'membership_cycle') {
                        $entry_is_active = $has_active_membership;
                    }
                    ?>
                    <div class="wt_flex wt_flex-col wt_gap-2<?php echo $entry_index > 0 ? ' wt_pt-4 wt_mt-1 wt_border-t wt_border-color' : ''; ?>">
                        <div class="wt_flex wt_items-center wt_text-content">
                            <?php if ($entry_membership_name !== ''): ?>
                                <span class="wt_text-base">
                                    <?php
                                    printf(
                                        /* translators: %s: Membership tier name. */
                                        esc_html__('Membership Tier: %s', 'wicket-acc'),
                                        esc_html($entry_membership_name)
                                    );
                                ?>
                                </span>
                            <?php elseif ($entry_membership_uuid !== ''): ?>
                                <span class="wt_text-base"><?php esc_html_e('Active Membership', 'wicket-acc'); ?></span>
                            <?php else: ?>
                                <span class="wt_text-base"><?php esc_html_e('No membership found', 'wicket-acc'); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($entry_is_active): ?>
                            <div class="wt_flex wt_items-center wt_gap-2">
                                <span class="wt_inline-block wt_w-2 wt_h-2 wt_rounded-full wt_bg-green-500" aria-hidden="true"></span>
                                <span class="wt_text-base wt_leading-none wt_text-content"><?php esc_html_e('Active Member', 'wicket-acc'); ?></span>
                            </div>
                        <?php elseif ($entry_membership_uuid !== ''): ?>
                            <div class="wt_flex wt_items-center wt_gap-2">
                                <span class="wt_inline-block wt_w-2 wt_h-2 wt_rounded-full wt_bg-gray-400" aria-hidden="true"></span>
                                <span class="wt_text-base wt_leading-none wt_text-content"><?php esc_html_e('Inactive Membership', 'wicket-acc'); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="wt_text-base wt_font-bold wt_text-content">
                            <span><?php esc_html_e('My Role(s):', 'wicket-acc'); ?></span>
                            <?php if (!empty($formatted_roles)): ?>
                                <?php echo esc_html(implode(', ', $formatted_roles)); ?>
                            <?php else: ?>
                                <?php esc_html_e('No roles assigned', 'wicket-acc'); ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($group_details)): ?>
                            <div class="wt_text-base wt_text-content">
                                <span class="wt_font-semibold"><?php esc_html_e('Group(s):', 'wicket-acc'); ?></span>
                                <?php
                                $group_labels = [];
                            foreach ($group_details as $group_detail) {
                                $label = $group_detail['name'] ?? '';
                                $type = $group_detail['type'] ?? '';
                                $tags = $group_detail['tags'] ?? null;
                                if ($label !== '' && $type !== '') {
                                    $label .= ' (' . ucwords(str_replace('_', ' ', $type)) . ')';
                                }
                                if (is_array($tags) && !empty($tags)) {
                                    $label .= ' [' . implode(', ', $tags) . ']';
                                }
                                if ($label !== '') {
                                    $group_labels[] = $label;
                                }
                            }
                            ?>
                                <?php echo esc_html(implode(', ', $group_labels)); ?>
                            </div>
                        <?php endif; ?>

                        <div class="wt_flex wt_items-center wt_gap-4 wt_mt-4">
                            <?php if ($can_edit_org): ?>
                                <?php
                            $profile_url_base = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-profile', '/my-account/organization-profile/');
                                $profile_params = ['org_uuid' => $org_id];
                                if ($roster_mode === 'membership_cycle' && $entry_membership_uuid !== '') {
                                    $profile_params['membership_uuid'] = $entry_membership_uuid;
                                }
                                if ($roster_mode === 'groups' && $primary_group_uuid !== '') {
                                    $profile_params['group_uuid'] = $primary_group_uuid;
                                }
                                ?>
                                <a href="<?php echo esc_url(add_query_arg($profile_params, $profile_url_base)); ?>"
                                    class="wt_inline-flex wt_items-center wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4">
                                    <?php esc_html_e('Edit Organization', 'wicket-acc'); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($has_any_roles): ?>
                                <span class="wt_px-2 wt_h-4 wt_bg-border-white" aria-hidden="true"></span>
                            <?php endif; ?>

                            <?php if ($is_membership_manager): ?>
                                <?php
                                $members_url_base = \OrgManagement\Helpers\Helper::get_my_account_page_url('organization-members', '/my-account/organization-members/');
                                $members_params = ['org_uuid' => $org_id];
                                if ($roster_mode === 'membership_cycle' && $entry_membership_uuid !== '') {
                                    $members_params['membership_uuid'] = $entry_membership_uuid;
                                }
                                if ($primary_group_uuid !== '') {
                                    $members_params['group_uuid'] = $primary_group_uuid;
                                }
                                ?>
                                <a href="<?php echo esc_url(add_query_arg($members_params, $members_url_base)); ?>"
                                    class="wt_inline-flex wt_items-center wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4">
                                    <?php esc_html_e('Manage Members', 'wicket-acc'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php echo '</div>'; ?>
</div>
