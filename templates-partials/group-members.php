<?php
/**
 * Group members partial.
 */

use OrgManagement\Services\AdditionalSeatsService;
use OrgManagement\Services\ConfigService;
use OrgManagement\Services\GroupService;
use OrgManagement\Services\MembershipService;

if (!defined('ABSPATH')) {
    exit;
}

$group_uuid = isset($_GET['group_uuid']) ? sanitize_text_field((string) $_GET['group_uuid']) : '';
$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field((string) $_GET['org_uuid']) : '';

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

$membership_service = new MembershipService();
$config_service = new ConfigService();
$additional_seats_service = new AdditionalSeatsService($config_service);
$orgman_config = \OrgManagement\Config\get_config();

$membership_uuid = '';
if ($org_uuid) {
    $membership_uuid = $membership_service->getMembershipForOrganization($org_uuid);
}

$result = $group_service->get_group_members($group_uuid, $org_identifier, [
    'page' => 1,
    'size' => $group_service->get_group_member_page_size(),
    'query' => '',
    'org_uuid' => $org_uuid,
]);

$members = $result['members'] ?? [];
$pagination = $result['pagination'] ?? [];
$query = $result['query'] ?? '';

$members_list_endpoint = \OrgManagement\Helpers\template_url() . 'group-members-list';
$members_list_target = 'group-members-list-container-' . sanitize_html_class($group_uuid);
$encoded_group_uuid = rawurlencode($group_uuid);
$encoded_org_uuid = rawurlencode($org_uuid);

$members_list_separator = str_contains($members_list_endpoint, '?') ? '&' : '?';
$search_action = "@get('{$members_list_endpoint}{$members_list_separator}group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1&query=' + encodeURIComponent(" . '$searchQuery' . '))';
$search_success = wp_sprintf("select('#%s') | set(html)", $members_list_target);
$signals = [
    'searchQuery' => $query,
    'searchSubmitted' => false,
];
$use_unified_view = (bool) ($orgman_config['groups']['ui']['use_unified_member_view'] ?? false);
if ($use_unified_view) {
    $mode = 'groups';
    $members_list_endpoint = $members_list_endpoint;
    $members_list_target = $members_list_target;
    $members = $members;
    $pagination = $pagination;
    $query = $query;
    include __DIR__ . '/members-view-unified.php';

    return;
}
?>
<div
    class="group-members wt_relative"
    data-signals:='{"membersLoading": false, "addMemberModalOpen": false, "addMemberSubmitting": false, "addMemberSuccess": false, "removeMemberModalOpen": false, "removeMemberSubmitting": false, "removeMemberSuccess": false, "currentRemoveMemberUuid": "", "currentRemoveMemberName": "", "currentRemoveMemberEmail": "", "currentRemoveMemberGroupMemberId": "", "currentRemoveMemberRole": ""}'
    data-on:datastar-fetch="evt.detail.type === 'started' && ($membersLoading = true); (evt.detail.type === 'finished' || evt.detail.type === 'error') && ($membersLoading = false)">
    <div class="members-loading-overlay wt_hidden wt_absolute wt_inset-0 wt_z-10 wt_bg-light-neutral-85 wt_backdrop-blur-xs"
        data-class:hidden="!$membersLoading" data-class:is-active="$membersLoading">
        <div class="wt_flex wt_h-full wt_w-full wt_flex-col wt_items-center wt_justify-center wt_gap-3">
            <div class="members-loading-spinner wt_h-10 wt_w-10 wt_rounded-full wt_border-4 wt_border-bg-interactive wt_border-t-transparent wt_animate-spin"
                aria-hidden="true"></div>
            <p class="wt_text-sm wt_font-medium wt_text-content" role="status" aria-live="polite">
                <?php esc_html_e('Loading...', 'wicket-acc'); ?>
            </p>
        </div>
    </div>
    <div class="members-search wt_flex wt_items-center wt_gap-2 wt_mb-6" data-signals:='<?php echo wp_json_encode($signals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>
        <div class="members-search__field wt_relative wt_w-full">
            <div class="members-search__icon wt_absolute wt_inset-y-0 wt_left-0 wt_flex wt_items-center wt_pl-3 wt_pointer-events-none">
                <svg class="wt_w-5 wt_h-5 wt_text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <?php
            $searchInputAction = '';
$searchLengthCondition = '((($searchQuery || \'\').length >= 3) || (($searchQuery || \'\').length === 0))';
$clearSubmittedWhenEmpty = '((($searchQuery || \'\').length === 0) && ($searchSubmitted = false)); ';
$searchInputAction = $clearSubmittedWhenEmpty . $searchLengthCondition . ' && ' . $search_action;
$searchSubmitAction = '$searchSubmitted = true; ' . $search_action;
$clearAction = "($searchQuery = '', $searchSubmitted = false, {$search_action})";
?>
            <input
                type="text"
                data-bind="searchQuery"
                class="members-search__input wt_border wt_border-color wt_text-content wt_text-sm wt_rounded-md wt_focus_ring-2 wt_focus_ring-bg-interactive wt_focus_border-bg-interactive wt_block wt_w-full wt_pl-10 wt_p-2.5"
                placeholder="<?php esc_attr_e('Start typing to search for members...', 'wicket-acc'); ?>"
                data-on:input__debounce.700ms="<?php echo esc_attr($searchInputAction); ?>"
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading"
                data-on:keydown.enter="<?php echo esc_attr($searchSubmitAction); ?>"
                data-on:keydown.enter__prevent-default="true">
        </div>
        <div class="members-search__actions wt_flex wt_items-center wt_gap-2">
            <button class="members-search__submit button button--primary wt_whitespace-nowrap component-button"
                data-on:click="<?php echo esc_attr($searchSubmitAction); ?>"
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-show="!$searchSubmitted"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading"><?php esc_html_e('Search', 'wicket-acc'); ?></button>
            <button class="members-search__clear button button--secondary wt_whitespace-nowrap component-button"
                data-on:click="<?php echo esc_attr($clearAction); ?>"
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-show="$searchSubmitted && $searchQuery && $searchQuery.trim() !== ''"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading"><?php esc_html_e('Clear', 'wicket-acc'); ?></button>
        </div>
    </div>

    <?php
$group_members = $members;
$group_pagination = $pagination;
$group_query = $query;
$group_members_list_endpoint = $members_list_endpoint;
$group_members_list_target = $members_list_target;
$use_unified_view = (bool) ($orgman_config['groups']['ui']['use_unified_member_view'] ?? false);
if ($use_unified_view) {
    $mode = 'groups';
    $members = $group_members;
    $pagination = $group_pagination;
    $query = $group_query;
    $membership_uuid = $membership_uuid;
    $show_edit_permissions = (bool) ($orgman_config['groups']['ui']['show_edit_permissions'] ?? false);
    $show_account_status = (bool) (($orgman_config['ui']['member_list']['account_status']['enabled'] ?? true));
    $show_add_member_button = true;
    $show_remove_button = true;
    $members_list_endpoint = $group_members_list_endpoint;
    $members_list_target = $group_members_list_target;
    include __DIR__ . '/members-view-unified.php';
} else {
    $use_unified_member_list = (bool) ($orgman_config['groups']['ui']['use_unified_member_list'] ?? false);
    if ($use_unified_member_list) {
        $mode = 'groups';
        $members = $group_members;
        $pagination = $group_pagination;
        $query = $group_query;
        $membership_uuid = $membership_uuid;
        $show_edit_permissions = (bool) ($orgman_config['groups']['ui']['show_edit_permissions'] ?? false);
        $show_account_status = (bool) (($orgman_config['ui']['member_list']['account_status']['enabled'] ?? true));
        $show_add_member_button = true;
        $show_remove_button = true;
        $members_list_endpoint = $group_members_list_endpoint;
        $members_list_target = $group_members_list_target;
        include __DIR__ . '/members-list-unified.php';
    } else {
        include __DIR__ . '/group-members-list.php';
    }
}
?>

    <?php
if ($use_unified_view) {
    return;
}
$can_purchase_seats = $org_uuid ? $additional_seats_service->can_purchase_additional_seats($org_uuid) : false;
$purchase_url = ($can_purchase_seats && $membership_uuid)
    ? $additional_seats_service->get_purchase_form_url($org_uuid, $membership_uuid)
    : '';
if ($can_purchase_seats && !empty($purchase_url)) :
    get_component('card-call-out', [
        'title' => __('Need More Seats?', 'wicket-acc'),
        'description' => __('Purchase additional seats for your organization membership to accommodate more team members.', 'wicket-acc'),
        'style' => 'secondary',
        'links' => [
            [
                'link' => [
                    'title' => __('Purchase Additional Seats', 'wicket-acc'),
                    'url' => $purchase_url,
                    'target' => '_self',
                ],
                'link_style' => 'secondary',
            ],
        ],
        'classes' => ['my-3'],
    ]);
endif;
?>

    <?php
$add_member_success_actions = "console.log('Group member added successfully'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false; \$addMemberSuccess = true; @get('{$members_list_endpoint}{$members_list_separator}group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1') >> select('#{$members_list_target}') | set(html); setTimeout(() => { \$addMemberSuccess = false; \$addMemberSubmitting = false; }, 3000);";
$add_member_error_actions = "console.error('Failed to add group member'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false;";
$remove_member_success_actions = "console.log('Group member removed successfully'); \$removeMemberSubmitting = false; \$membersLoading = false; \$removeMemberModalOpen = false; \$removeMemberSuccess = true; @get('{$members_list_endpoint}{$members_list_separator}group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1') >> select('#{$members_list_target}') | set(html); setTimeout(() => { \$removeMemberSuccess = false; \$removeMemberSubmitting = false; }, 3000);";
$remove_member_error_actions = "console.error('Failed to remove group member'); \$removeMemberSubmitting = false; \$membersLoading = false; \$removeMemberModalOpen = false;";
$add_member_endpoint = OrgManagement\Helpers\TemplateHelper::template_url() . 'process/add-group-member';
$remove_member_endpoint = OrgManagement\Helpers\TemplateHelper::template_url() . 'process/remove-group-member';
$groups_config = is_array($orgman_config['groups'] ?? null) ? $orgman_config['groups'] : [];
$member_role = $groups_config['member_role'] ?? 'member';
$observer_role = $groups_config['observer_role'] ?? 'observer';
?>

    <dialog id="groupMembersAddModal"
        class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
        data-show="$addMemberModalOpen"
        data-effect="if ($addMemberModalOpen) el.showModal(); else el.close();"
        data-on:close="($membersLoading = false); $addMemberModalOpen = false">
        <div class="wt_bg-white wt_p-6 wt_relative" data-on:click__outside__capture="$addMemberModalOpen = false">
            <button type="button" class="wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
                data-on:click="$addMemberModalOpen = false" data-class:hidden="$addMemberSuccess">
                ×
            </button>

            <h2 class="wt_text-2xl wt_font-semibold wt_mb-4">
                <?php esc_html_e('Add Member', 'wicket-acc'); ?>
            </h2>

            <form
                method="post"
                class="wt_flex wt_flex-col wt_gap-4"
                data-on:submit="if(!$addMemberSubmitting){ $addMemberSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($add_member_endpoint); ?>', { contentType: 'form' }); }"
                data-on:submit__prevent-default="true"
                data-on:success="<?php echo esc_attr($add_member_success_actions); ?>"
                data-on:error="<?php echo esc_attr($add_member_error_actions); ?>"
                data-on:reset="$addMemberSubmitting = false">
                <input type="hidden" name="group_uuid" value="<?php echo esc_attr($group_uuid); ?>">
                <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-add-group-member')); ?>">

                <div>
                    <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="group-member-first-name">
                        <?php esc_html_e('First Name', 'wicket-acc'); ?>
                    </label>
                    <input id="group-member-first-name" name="first_name" type="text"
                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2" required>
                </div>
                <div>
                    <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="group-member-last-name">
                        <?php esc_html_e('Last Name', 'wicket-acc'); ?>
                    </label>
                    <input id="group-member-last-name" name="last_name" type="text"
                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2" required>
                </div>
                <div>
                    <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="group-member-email">
                        <?php esc_html_e('Email Address', 'wicket-acc'); ?>
                    </label>
                    <input id="group-member-email" name="email" type="email"
                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2"
                        placeholder="<?php echo esc_attr(__('user@mail.com', 'wicket-acc')); ?>" required>
                </div>
                <div>
                    <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="group-member-role">
                        <?php esc_html_e('Role', 'wicket-acc'); ?>
                    </label>
                    <select id="group-member-role" name="role"
                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                        <option value="<?php echo esc_attr($member_role); ?>"><?php esc_html_e('Member', 'wicket-acc'); ?></option>
                        <option value="<?php echo esc_attr($observer_role); ?>"><?php esc_html_e('Observer', 'wicket-acc'); ?></option>
                    </select>
                </div>

                <div class="wt_flex wt_justify-end wt_gap-3 wt_pt-4" data-class:hidden="$addMemberSuccess">
                    <button type="button" class="button button--secondary component-button"
                        data-on:click="$addMemberModalOpen = false"
                        data-class="{ 'wt_pointer-events-none': $addMemberSubmitting, 'wt_opacity-50': $addMemberSubmitting }"
                        data-attr:aria-disabled="$addMemberSubmitting ? 'true' : 'false'"><?php esc_html_e('Cancel', 'wicket-acc'); ?></button>
                    <button type="submit" class="button button--primary wt_inline-flex wt_items-center wt_gap-2 component-button"
                        data-class="{ 'wt_pointer-events-none': $addMemberSubmitting, 'wt_opacity-50': $addMemberSubmitting }"
                        data-attr:aria-disabled="$addMemberSubmitting ? 'true' : 'false'">
                        <span data-class:hidden="$addMemberSubmitting">
                            <?php esc_html_e('Add Member', 'wicket-acc'); ?>
                        </span>
                        <svg class="wt_h-5 wt_w-5 wt_text-button-label-reversed wt_hidden"
                            data-class:hidden="!$addMemberSubmitting" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle class="wt_opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="wt_opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="groupMembersRemoveModal" class="modal wt_m-auto max_wt_md wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
        data-show="$removeMemberModalOpen"
        data-effect="if ($removeMemberModalOpen) el.showModal(); else el.close();"
        data-on:close="($membersLoading = false); $removeMemberModalOpen = false">
        <div class="wt_bg-white wt_p-6 wt_relative">
            <button type="button" class="wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
                data-on:click="$removeMemberModalOpen = false" data-class_wt_hidden="$removeMemberSuccess">
                ×
            </button>
            <h2 class="wt_text-2xl wt_font-semibold wt_mb-4"><?php esc_html_e('Remove Member', 'wicket-acc'); ?></h2>
            <div id="remove-member-messages"></div>

            <div data-class_wt_hidden="$removeMemberSuccess">
                <p class="wt_mb-6">
                    <span data-class_wt_hidden="$currentRemoveMemberName === ''">
                        <?php echo esc_html__('Are you sure you want to remove this member from the group?', 'wicket-acc'); ?>
                    </span>
                    <span data-class_wt_hidden="$currentRemoveMemberName !== ''">
                        <?php echo esc_html__('Are you sure you want to remove', 'wicket-acc'); ?>
                        <span data-text="$currentRemoveMemberName"></span>
                        <?php echo esc_html__('from this group?', 'wicket-acc'); ?>
                    </span>
                    <br>
                    <?php esc_html_e('This action cannot be undone.', 'wicket-acc'); ?>
                </p>

                <form
                    method="POST"
                    data-on:submit="$removeMemberSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($remove_member_endpoint); ?>', { contentType: 'form' })"
                    data-on:submit__prevent-default="true"
                    data-on:success="<?php echo esc_attr($remove_member_success_actions); ?>"
                    data-on:error="<?php echo esc_attr($remove_member_error_actions); ?>"
                    data-on:reset="$removeMemberSubmitting = false; $membersLoading = false">
                    <input type="hidden" name="group_uuid" value="<?php echo esc_attr($group_uuid); ?>">
                    <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                    <input type="hidden" name="person_uuid" data-attr:value="$currentRemoveMemberUuid">
                    <input type="hidden" name="group_member_id" data-attr:value="$currentRemoveMemberGroupMemberId">
                    <input type="hidden" name="role" data-attr:value="$currentRemoveMemberRole">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-remove-group-member')); ?>">

                    <div class="wt_flex wt_justify-end wt_gap-3">
                        <button
                            type="button"
                            data-on:click="$removeMemberModalOpen = false"
                            class="button button--secondary wt_px-4 wt_py-2 wt_text-sm component-button"
                            data-class:disabled="$removeMemberSubmitting"
                            data-attr:disabled="$removeMemberSubmitting"><?php esc_html_e('Cancel', 'wicket-acc'); ?></button>
                        <button
                            type="submit"
                            class="button button--danger wt_inline-flex wt_items-center wt_gap-2 wt_px-4 wt_py-2 wt_text-sm component-button"
                            data-class:disabled="$removeMemberSubmitting"
                            data-attr:disabled="$removeMemberSubmitting">
                            <span data-class_wt_hidden="$removeMemberSubmitting">
                                <?php esc_html_e('Remove Member', 'wicket-acc'); ?>
                            </span>
                            <svg
                                class="wt_h-4 wt_w-4 wt_text-white wt_hidden"
                                data-class_wt_hidden="!$removeMemberSubmitting"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle class="wt_opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="wt_opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>
</div>
