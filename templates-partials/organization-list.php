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
            class="wt_mt-4 wt_px-4 wt_py-2 wt_bg-red-600 wt_text-white wt_rounded-md wt_hover_bg-red-700 wt_focus_outline-hidden wt_focus_ring-2 wt_focus_ring-red-500"><?php esc_html_e('Try Again', 'wicket-acc'); ?></button>
    </div>
<?php
    return;
}

// Handle empty organizations data.
// In groups mode, organizations may be derived from manageable group memberships.
if (($roster_mode !== 'groups') && (empty($organizations) || !is_array($organizations))) {
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
        // For groups strategy landing, list all active group memberships and let group pages enforce manage access.
        'include_all_roles' => $roster_mode === 'groups',
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
        $group = $group_item['group'] ?? [];
        $group_id = (string) ($group['id'] ?? '');
        if ($group_id === '') {
            $logger->debug('[OrgRoster] Skipping group item missing group id', [
                'source' => 'wicket-orgroster',
                'group_item' => $group_item,
            ]);
            continue;
        }

        $org_uuid = (string) ($group_item['org_uuid'] ?? '');
        $org_identifier = (string) ($group_item['org_identifier'] ?? '');
        $org_map_key = $org_uuid !== ''
            ? $org_uuid
            : ($org_identifier !== '' ? 'org-scope-' . md5($org_identifier) : 'group-scope-' . md5($group_id));
        if ($org_map_key === '') {
            $logger->debug('[OrgRoster] Skipping group item missing organization scope', [
                'source' => 'wicket-orgroster',
                'group_item' => $group_item,
            ]);
            continue;
        }
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
        if (!isset($groups_by_org[$org_map_key])) {
            $groups_by_org[$org_map_key] = [];
        }
        $groups_by_org[$org_map_key][] = [
            'id' => $group_id,
            'name' => $group_name,
            'type' => $group_type,
            'tags' => $group_tags,
            'org_uuid' => $org_uuid,
            'org_identifier' => $org_identifier,
            'org_name' => (string) ($group_item['org_name'] ?? ''),
            'role_slug' => (string) ($group_item['role_slug'] ?? ''),
            'can_manage' => !empty($group_item['can_manage']),
        ];
        $logger->debug('[OrgRoster] Group mapped to org', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_uuid,
            'org_map_key' => $org_map_key,
            'org_identifier' => $org_identifier,
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

if ($roster_mode === 'groups') {
    $manageable_groups = [];
    $seen_group_ids = [];
    foreach ($groups_by_org as $group_details) {
        foreach ((array) $group_details as $group_detail) {
            $group_id = (string) ($group_detail['id'] ?? '');
            if ($group_id === '' || isset($seen_group_ids[$group_id])) {
                continue;
            }
            $seen_group_ids[$group_id] = true;

            $group_name = (string) ($group_detail['name'] ?? '');
            if ($group_name === '') {
                $group_name = __('Unknown Group', 'wicket-acc');
            }

            $manageable_groups[] = [
                'group_uuid' => $group_id,
                'group_name' => $group_name,
                'org_uuid' => (string) ($group_detail['org_uuid'] ?? ''),
                'org_identifier' => (string) ($group_detail['org_identifier'] ?? ''),
                'org_name' => (string) ($group_detail['org_name'] ?? ''),
                'role_slug' => (string) ($group_detail['role_slug'] ?? ''),
                'can_manage' => !empty($group_detail['can_manage']),
            ];
        }
    }

    usort($manageable_groups, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['group_name'] ?? ''), (string) ($b['group_name'] ?? ''));
    });

    $groups_count = count($manageable_groups);
    $group_members_url = \OrgManagement\Helpers\Helper::get_my_account_page_url(
        'organization-members',
        '/my-account/organization-members/'
    );

    if ($groups_count === 1) {
        $single_group = $manageable_groups[0];
        $redirect_args = [];
        foreach ($_GET as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            if ($key === 'org_id' || $key === 'org_uuid' || $key === 'group_uuid') {
                continue;
            }
            $redirect_args[$key] = sanitize_text_field(wp_unslash((string) $value));
        }
        $redirect_args['group_uuid'] = (string) ($single_group['group_uuid'] ?? '');
        if ((string) ($single_group['org_uuid'] ?? '') !== '') {
            $redirect_args['org_uuid'] = (string) $single_group['org_uuid'];
        }

        wp_redirect(add_query_arg($redirect_args, $group_members_url));
        exit;
    }

    if ($groups_count === 0) {
        echo '<div id="group-list-container"><p>' . esc_html__('You currently have no groups to manage members for.', 'wicket-acc') . '</p></div>';

        return;
    }
    ?>
    <div id="group-list-container">
        <p class="mb-2"><?php echo esc_html(__('Groups Found:', 'wicket-acc') . ' ' . (int) $groups_count); ?></p>
        <div class="wt_w-full wt_flex wt_flex-col wt_gap-4" role="list">
            <?php foreach ($manageable_groups as $group_item) :
                $item_params = [
                    'group_uuid' => (string) ($group_item['group_uuid'] ?? ''),
                ];
                if ((string) ($group_item['org_uuid'] ?? '') !== '') {
                    $item_params['org_uuid'] = (string) $group_item['org_uuid'];
                }
                $can_manage_group = !empty($group_item['can_manage']);
                $group_profile_url_base = \OrgManagement\Helpers\Helper::get_my_account_page_url(
                    'organization-profile',
                    '/my-account/organization-profile/'
                );
                $group_profile_url = add_query_arg($item_params, $group_profile_url_base);
                $group_members_manage_url = add_query_arg($item_params, $group_members_url);
                $group_role_slug = sanitize_key((string) ($group_item['role_slug'] ?? ''));
                $group_role_label = $group_role_slug !== ''
                    ? ucwords(str_replace('_', ' ', $group_role_slug))
                    : '';
                $group_org_label = (string) ($group_item['org_name'] ?? '');
                if ($group_org_label === '') {
                    $group_org_candidates = array_values(array_unique(array_filter([
                        (string) ($group_item['org_uuid'] ?? ''),
                        (string) ($group_item['org_identifier'] ?? ''),
                    ], static function ($value): bool {
                        return is_string($value) && trim($value) !== '';
                    })));
                    if (!empty($group_org_candidates) && function_exists('wicket_get_organization')) {
                        static $org_name_cache = [];
                        foreach ($group_org_candidates as $group_org_candidate) {
                            if (!array_key_exists($group_org_candidate, $org_name_cache)) {
                                $resolved_name = '';
                                try {
                                    $organization_response = wicket_get_organization($group_org_candidate);
                                    if (is_array($organization_response) && isset($organization_response['data']['attributes'])) {
                                        $org_attrs = (array) $organization_response['data']['attributes'];
                                        $resolved_name = (string) (
                                            $org_attrs['legal_name']
                                            ?? $org_attrs['legal_name_en']
                                            ?? $org_attrs['name']
                                            ?? ''
                                        );
                                    }
                                } catch (\Throwable $e) {
                                    $resolved_name = '';
                                }
                                $org_name_cache[$group_org_candidate] = $resolved_name;
                            }

                            $candidate_label = (string) ($org_name_cache[$group_org_candidate] ?? '');
                            if ($candidate_label !== '') {
                                $group_org_label = $candidate_label;
                                break;
                            }
                        }
                    }
                }
                if ($group_org_label === '') {
                    $group_org_label = (string) ($group_item['org_identifier'] ?? '');
                }
                ?>
                <div class="wt_w-full wt_rounded-card-accent wt_p-4 wt_mb-1 wt_hover_shadow-sm wt_transition-shadow wt_bg-card wt_border wt_border-color wt_decoration-none"
                    role="listitem">
                    <div class="wt_text-xl wt_font-semibold wt_text-content"><?php echo esc_html((string) $group_item['group_name']); ?></div>
                    <?php if ($group_org_label !== '') : ?>
                        <div class="wt_text-sm wt_text-content wt_mt-1">
                            <?php echo esc_html(sprintf(__('Organization: %s', 'wicket-acc'), $group_org_label)); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($group_role_label !== '') : ?>
                        <div class="wt_text-sm wt_text-content wt_mt-1">
                            <?php echo esc_html(sprintf(__('My Role: %s', 'wicket-acc'), $group_role_label)); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($can_manage_group) : ?>
                        <div class="wt_flex wt_items-center wt_gap-4 wt_mt-4">
                            <a href="<?php echo esc_url($group_profile_url); ?>"
                                class="wt_inline-flex wt_items-center wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4">
                                <?php esc_html_e('Group Profile', 'wicket-acc'); ?>
                            </a>
                            <span class="wt_px-2 wt_h-4 wt_bg-border-white" aria-hidden="true"></span>
                            <a href="<?php echo esc_url($group_members_manage_url); ?>"
                                class="wt_inline-flex wt_items-center wt_text-primary-600 wt_hover_text-primary-700 underline underline-offset-4">
                                <?php esc_html_e('Manage Members', 'wicket-acc'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return;
}

if (!is_array($organizations)) {
    $organizations = [];
}

if ($roster_mode === 'groups' && !empty($groups_by_org)) {
    $org_index = [];
    foreach ($organizations as $organization) {
        $existing_org_uuid = (string) ($organization['id'] ?? '');
        if ($existing_org_uuid !== '') {
            $org_index[$existing_org_uuid] = true;
        }
    }

    foreach ($groups_by_org as $group_org_key => $group_details) {
        if ($group_org_key === '' || isset($org_index[$group_org_key])) {
            continue;
        }

        $group_org_name = '';
        $group_fallback_name = '';
        $group_org_uuid = '';
        $group_org_identifier = '';
        if (!empty($group_details) && is_array($group_details)) {
            $group_org_uuid = (string) ($group_details[0]['org_uuid'] ?? '');
            $group_org_identifier = (string) ($group_details[0]['org_identifier'] ?? '');
            $group_org_name = (string) ($group_details[0]['org_name'] ?? '');
            foreach ($group_details as $group_detail) {
                $candidate_group_name = (string) ($group_detail['name'] ?? '');
                if ($candidate_group_name !== '') {
                    $group_fallback_name = $candidate_group_name;
                    break;
                }
            }
        }

        $organizations[] = [
            'id' => $group_org_key,
            'resolved_org_uuid' => $group_org_uuid,
            'org_identifier' => $group_org_identifier,
            'org_name' => $group_org_name !== ''
                ? $group_org_name
                : ($group_fallback_name !== '' ? $group_fallback_name : __('Unknown', 'wicket-acc')),
            'roles' => [],
        ];
        $org_index[$group_org_key] = true;
    }
}

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
    $orgman_config = \OrgManagement\Config\get_config();
    $org_list_config = is_array($orgman_config['ui']['organization_list'] ?? null)
        ? $orgman_config['ui']['organization_list']
        : [];
    $org_page_size = max(1, (int) ($org_list_config['page_size'] ?? 5));
    $org_total_items = count($organizations);
    $org_total_pages = max(1, (int) ceil($org_total_items / $org_page_size));
    $org_page_raw = isset($_GET['org_page']) ? wp_unslash($_GET['org_page']) : 1;
    if (!is_scalar($org_page_raw)) {
        $org_page_raw = 1;
    }
    $org_page = max(1, absint((string) $org_page_raw));
    if ($org_page > $org_total_pages) {
        $org_page = $org_total_pages;
    }
    $org_offset = ($org_page - 1) * $org_page_size;
    $organizations_page = array_slice($organizations, $org_offset, $org_page_size);

    // Display organization count
    $count = $org_total_items;
    echo "<p class='mb-2'>" . __('Organizations Found:', 'wicket-acc') . ' ' . (int) $count . '</p>';

// Start organization list
echo '<div class="wt_w-full wt_flex wt_flex-col wt_gap-4" role="list">';
// Initialize membership service once for the loop
$membership_service = new \OrgManagement\Services\MembershipService();
foreach ($organizations_page as $org) :
    $org_id = (string) ($org['id'] ?? '');
    $org_name = $org['org_name'] ?? __('Unknown', 'wicket-acc');
    $group_details = $groups_by_org[$org_id] ?? [];
    $resolved_org_uuid = (string) ($org['resolved_org_uuid'] ?? '');
    if ($resolved_org_uuid === '' && !empty($group_details)) {
        $resolved_org_uuid = (string) ($group_details[0]['org_uuid'] ?? '');
    }
    $org_uuid_for_scope = $resolved_org_uuid !== '' ? $resolved_org_uuid : $org_id;
    if (str_starts_with($org_uuid_for_scope, 'org-scope-')) {
        $org_uuid_for_scope = '';
    }
    try {
        if ($org_uuid_for_scope !== '') {
            $tag_name = $group_service->get_roster_tag_name();
            $groups_response = wicket_api_client()->get('/groups', [
                'query' => [
                    'page' => [
                        'number' => 1,
                        'size' => 50,
                    ],
                    'filter' => [
                        'organization_uuid_eq' => $org_uuid_for_scope,
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
                'org_uuid' => $org_uuid_for_scope,
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
                        'organization_uuid_eq' => $org_uuid_for_scope,
                    ],
                    'sort' => 'name_en',
                ],
            ]);
            $groups_all = is_array($groups_response_all) ? ($groups_response_all['data'] ?? []) : [];
            $logger->debug('[OrgRoster] Org groups tags via /groups (no tag filter)', [
                'source' => 'wicket-orgroster',
                'org_uuid' => $org_uuid_for_scope,
                'count' => count($groups_all),
                'group_ids' => array_map(static function ($group_item) {
                    return $group_item['id'] ?? '';
                }, $groups_all),
                'group_tags' => array_map(static function ($group_item) {
                    $attrs = is_array($group_item) ? ($group_item['attributes'] ?? []) : [];

                    return $attrs['tags'] ?? null;
                }, $groups_all),
            ]);
        }
    } catch (\Throwable $e) {
        $logger->error('[OrgRoster] Org groups tags fetch failed', [
            'source' => 'wicket-orgroster',
            'org_uuid' => $org_uuid_for_scope,
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
    $membership_uuid = $org_uuid_for_scope !== ''
        ? $membership_service->getMembershipForOrganization($org_uuid_for_scope)
        : '';
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
            'is_active' => $roster_mode === 'groups'
                ? true
                : \OrgManagement\Helpers\PermissionHelper::has_active_membership($org_uuid_for_scope),
        ];
    }

    // Get user roles for this organization using PermissionHelper
    $raw_roles = $org_uuid_for_scope !== ''
        ? \OrgManagement\Helpers\PermissionHelper::get_user_org_roles($org_uuid_for_scope)
        : [];
    if (empty($raw_roles) && !empty($org['roles']) && is_array($org['roles'])) {
        $raw_roles = $org['roles'];
    }
    if (empty($raw_roles) && $roster_mode === 'groups' && !empty($group_details)) {
        $group_role_slugs = array_values(array_unique(array_filter(array_map(static function ($group_detail) {
            return sanitize_key((string) ($group_detail['role_slug'] ?? ''));
        }, $group_details))));
        if (!empty($group_role_slugs)) {
            $raw_roles = $group_role_slugs;
        }
    }

    // Prepare roles for display using PermissionHelper
    $formatted_roles = \OrgManagement\Helpers\PermissionHelper::format_roles_for_display($raw_roles);

    $primary_group_uuid = '';
    if ($roster_mode === 'groups' && !empty($group_details)) {
        $primary_group_uuid = $group_details[0]['id'] ?? '';
    }
    $has_active_membership = $roster_mode === 'groups'
        ? true
        : \OrgManagement\Helpers\PermissionHelper::has_active_membership($org_uuid_for_scope);
    $is_group_manager = ($roster_mode === 'groups' && !empty($group_details));
    $is_membership_manager = $is_group_manager
        ? true
        : \OrgManagement\Helpers\PermissionHelper::is_membership_manager($org_uuid_for_scope);
    $can_edit_org = $roster_mode === 'groups'
        ? $is_group_manager
        : \OrgManagement\Helpers\PermissionHelper::can_edit_organization($org_uuid_for_scope);
    $has_any_roles = $is_group_manager
        ? true
        : \OrgManagement\Helpers\PermissionHelper::has_management_roles($org_uuid_for_scope);
    $org_uuid_for_links = $org_uuid_for_scope;
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
    $card_template = __DIR__ . '/card-organization-direct-cascade.php';
    if ($roster_mode === 'groups') {
        $card_template = __DIR__ . '/card-organization-groups.php';
    } elseif ($roster_mode === 'membership_cycle') {
        $card_template = __DIR__ . '/card-organization-membership-cycle.php';
    }
    include $card_template;
endforeach; ?>

    <?php echo '</div>'; ?>

    <?php if ($org_total_pages > 1) : ?>
        <?php
        $base_query_args = [];
        foreach ($_GET as $query_key => $query_value) {
            if (is_scalar($query_value)) {
                $base_query_args[$query_key] = sanitize_text_field(wp_unslash((string) $query_value));
            }
        }
        unset($base_query_args['org_page']);

        $base_list_url = \OrgManagement\Helpers\Helper::get_my_account_page_url(
            'organization-management',
            '/my-account/organization-management/'
        );

        $build_page_url = static function (int $page_number) use ($base_query_args, $base_list_url): string {
            $args = $base_query_args;
            if ($page_number > 1) {
                $args['org_page'] = $page_number;
            }

            return add_query_arg($args, $base_list_url);
        };

        $first_item = $org_offset + 1;
        $last_item = min($org_total_items, $org_offset + count($organizations_page));
        ?>
        <nav class="members-pagination wt_mt-6 wt_flex wt_flex-col wt_gap-4" aria-label="<?php esc_attr_e('Organizations pagination', 'wicket-acc'); ?>">
            <div class="members-pagination__info wt_w-full wt_text-left wt_text-sm wt_text-content">
                <?php
                printf(
                    /* translators: 1: first item number, 2: last item number, 3: total items. */
                    esc_html__('Showing %1$d-%2$d of %3$d organizations', 'wicket-acc'),
                    (int) $first_item,
                    (int) $last_item,
                    (int) $org_total_items
                );
                ?>
            </div>
            <div class="members-pagination__controls wt_w-full wt_flex wt_items-center wt_gap-2 wt_justify-end wt_self-end">
                <?php if ($org_page > 1) : ?>
                    <a href="<?php echo esc_url($build_page_url($org_page - 1)); ?>"
                        class="members-pagination__btn members-pagination__btn--prev button button--secondary wt_px-3 wt_py-2 wt_text-sm">
                        <?php esc_html_e('Previous', 'wicket-acc'); ?>
                    </a>
                <?php endif; ?>
                <div class="members-pagination__pages wt_flex wt_items-center wt_gap-1">
                    <?php for ($i = 1; $i <= $org_total_pages; $i++) : ?>
                        <a href="<?php echo esc_url($build_page_url($i)); ?>"
                            class="members-pagination__btn members-pagination__btn--page button wt_px-3 wt_py-2 wt_text-sm <?php echo $i === $org_page ? 'button--primary' : 'button--secondary'; ?>"
                            <?php if ($i === $org_page) : ?>aria-current="page"<?php endif; ?>>
                            <?php echo esc_html((string) $i); ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php if ($org_page < $org_total_pages) : ?>
                    <a href="<?php echo esc_url($build_page_url($org_page + 1)); ?>"
                        class="members-pagination__btn members-pagination__btn--next button button--secondary wt_px-3 wt_py-2 wt_text-sm">
                        <?php esc_html_e('Next', 'wicket-acc'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
