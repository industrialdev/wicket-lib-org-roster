<?php

declare(strict_types=1);

use OrgManagement\Helpers as OrgHelpers;

if (!defined('ABSPATH')) {
    exit;
}

$mode = isset($mode) ? (string) $mode : 'direct';
$org_uuid = isset($org_uuid) ? (string) $org_uuid : '';
$group_uuid = isset($group_uuid) ? (string) $group_uuid : '';
$org_identifier = isset($org_identifier) ? (string) $org_identifier : '';

$orgman_config = \OrgManagement\Config\get_config();
$view_config = $orgman_config['ui']['member_view'] ?? [];
$groups_view_config = $orgman_config['groups']['ui'] ?? [];

$search_clear_requires_submit = (bool) ($view_config['search_clear_requires_submit'] ?? false);
if ($mode === 'groups' && isset($groups_view_config['search_clear_requires_submit'])) {
    $search_clear_requires_submit = (bool) $groups_view_config['search_clear_requires_submit'];
}

$members = isset($members) && is_array($members) ? $members : [];
$pagination = isset($pagination) && is_array($pagination) ? $pagination : [];
$query = isset($query) ? (string) $query : '';
$membersResult = isset($membersResult) && is_array($membersResult) ? $membersResult : [];

$members_list_target = isset($members_list_target)
    ? (string) $members_list_target
    : 'members-list-container-' . sanitize_html_class($org_uuid ?: 'default');

$members_list_endpoint = isset($members_list_endpoint)
    ? (string) $members_list_endpoint
    : OrgHelpers\template_url() . 'members-list';

$members_list_separator = str_contains($members_list_endpoint, '?') ? '&' : '?';

if ($mode === 'groups') {
    $members_list_target = $members_list_target ?: 'group-members-list-container-' . sanitize_html_class($group_uuid ?: 'default');
}

$encoded_org_uuid = rawurlencode((string) $org_uuid);
$encoded_group_uuid = rawurlencode((string) $group_uuid);

$search_action = '';
if ($mode === 'groups') {
    $search_action = "@get('{$members_list_endpoint}{$members_list_separator}group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1&query=' + encodeURIComponent(" . '$searchQuery' . '))';
} else {
    $search_action = "@get('{$members_list_endpoint}{$members_list_separator}org_uuid={$encoded_org_uuid}&page=1&query=' + encodeURIComponent(" . '$searchQuery' . '))';
}
$search_success = '$membersLoading = false; ' . wp_sprintf("select('#%s') | set(html)", $members_list_target);

$signals = [
    'searchQuery' => $query,
    'searchSubmitted' => false,
];

$membership_uuid = isset($membership_uuid) ? (string) $membership_uuid : '';
$membership_service = new OrgManagement\Services\MembershipService();
$config_service = new OrgManagement\Services\ConfigService();
$additional_seats_service = new OrgManagement\Services\AdditionalSeatsService($config_service);
if ($membership_uuid === '' && $org_uuid !== '') {
    $membership_uuid = $membership_service->getMembershipForOrganization($org_uuid);
}
$encoded_membership_uuid = rawurlencode((string) $membership_uuid);
$membership_query_fragment = $membership_uuid !== '' ? "&membership_uuid={$encoded_membership_uuid}" : '';
if ($mode !== 'groups') {
    $search_action = "@get('{$members_list_endpoint}{$members_list_separator}org_uuid={$encoded_org_uuid}{$membership_query_fragment}&page=1&query=' + encodeURIComponent(" . '$searchQuery' . '))';
}

$can_purchase_seats = $org_uuid ? $additional_seats_service->can_purchase_additional_seats($org_uuid) : false;
$purchase_url = ($can_purchase_seats && $membership_uuid)
    ? $additional_seats_service->get_purchase_form_url($org_uuid, $membership_uuid)
    : '';

$show_edit_permissions = isset($show_edit_permissions)
    ? (bool) $show_edit_permissions
    : (bool) ($orgman_config['ui']['member_list']['show_edit_permissions'] ?? true);
if ($mode === 'groups') {
    $show_edit_permissions = (bool) ($orgman_config['groups']['ui']['show_edit_permissions'] ?? false);
}
$member_list_config = is_array($orgman_config['ui']['member_list'] ?? null)
    ? $orgman_config['ui']['member_list']
    : [];
$show_remove_button_by_config = (bool) ($member_list_config['show_remove_button'] ?? true);
$show_bulk_upload = (bool) ($member_list_config['show_bulk_upload'] ?? false);
$show_add_member_button = true;
$show_remove_button = true;
if ($mode !== 'groups') {
    $show_add_member_button = OrgHelpers\PermissionHelper::can_add_members($org_uuid);
    $show_remove_button = $show_remove_button_by_config && OrgHelpers\PermissionHelper::can_remove_members($org_uuid);
}

$search_submit_action = '$membersLoading = true; $searchSubmitted = true; ' . $search_action;
$search_input_action = '';
if ($search_action !== '') {
    $search_length_condition = '((($searchQuery || \'\').length >= 3) || (($searchQuery || \'\').length === 0))';
    $search_input_action = '$membersLoading = true; ' . $search_length_condition . ' && ' . $search_action;
}
$clear_action = '(' . '$membersLoading' . ' = true, ' . '$searchQuery' . " = '', " . '$searchSubmitted' . " = false, {$search_action})";

?>
<div class="members-list wt_relative" data-member-view="unified"
    data-signals:='{"membersLoading": false, "bulkUploadModalOpen": false, "bulkUploadSubmitting": false, "addMemberModalOpen": false, "addMemberSubmitting": false, "addMemberSuccess": false, "removeMemberModalOpen": false, "removeMemberSubmitting": false, "removeMemberSuccess": false, "currentRemoveMemberUuid": "", "currentRemoveMemberName": "", "currentRemoveMemberEmail": "", "currentRemoveMemberConnectionId": "", "currentRemoveMemberPersonMembershipId": "", "currentRemoveMemberGroupMemberId": "", "currentRemoveMemberRole": "", "editPermissionsModalOpen": false, "editPermissionsSubmitting": false, "editPermissionsSuccess": false, "currentMemberUuid": "", "currentMemberName": "", "currentMemberRoles": [], "currentMemberRelationshipType": "", "currentMemberDescription": ""}'
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

    <div class="members-search wt_flex wt_items-center wt_gap-2 wt_mb-6"
        data-signals:='<?php echo wp_json_encode($signals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>
        <div class="members-search__field wt_relative wt_w-full">
            <div class="members-search__icon wt_absolute wt_inset-y-0 wt_left-0 wt_flex wt_items-center wt_pl-3 wt_pointer-events-none">
                <svg class="wt_w-5 wt_h-5 wt_text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input
                type="text"
                id="<?php echo esc_attr(($mode === 'groups' ? 'group' : 'org') . '-members-search-' . sanitize_html_class($mode === 'groups' ? $group_uuid : $org_uuid)); ?>"
                data-bind="searchQuery"
                class="members-search__input wt_border wt_border-color wt_text-content wt_text-sm wt_rounded-md wt_focus_ring-2 wt_focus_ring-bg-interactive wt_focus_border-bg-interactive wt_block wt_w-full wt_pl-10 wt_p-2.5"
                placeholder="<?php esc_attr_e('Start typing to search for members...', 'wicket-acc'); ?>"
                <?php if ($search_input_action !== '') : ?>
                data-on:input__debounce.700ms="<?php echo esc_attr($search_input_action); ?>"
                <?php endif; ?>
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading">
        </div>
        <div class="members-search__actions wt_flex wt_items-center wt_gap-2">
            <button type="button" class="members-search__submit button button--primary wt_whitespace-nowrap component-button"
                data-on:click="<?php echo esc_attr($search_submit_action); ?>"
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-on:error="$membersLoading = false"
                data-show="!$searchQuery || $searchQuery.trim() === ''"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading">
                <?php esc_html_e('Search', 'wicket-acc'); ?>
            </button>
            <button type="button" class="members-search__clear button button--secondary wt_whitespace-nowrap component-button"
                data-on:click="<?php echo esc_attr($clear_action); ?>"
                data-on:success="<?php echo esc_attr($search_success); ?>"
                data-on:error="$membersLoading = false"
                data-show="$searchQuery && $searchQuery.trim() !== ''"
                data-indicator:members-loading
                data-attr:disabled="$membersLoading">
                <?php esc_html_e('Clear', 'wicket-acc'); ?>
            </button>
        </div>
    </div>

    <?php if ($mode === 'groups') : ?>
        <div id="group-member-messages" class="wt_mb-3"></div>
    <?php endif; ?>

    <?php
    $members_list_endpoint = $members_list_endpoint;
$members_list_target = $members_list_target;
$show_edit_permissions = $show_edit_permissions;
$show_account_status = true;
$show_add_member_button = $show_add_member_button;
    $show_remove_button = $show_remove_button;
include __DIR__ . '/members-list-unified.php';
?>

    <?php if ($can_purchase_seats && !empty($purchase_url)) : ?>
        <?php
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
        ?>
    <?php endif; ?>

    <?php
    $add_member_success_actions = "console.log('Member added successfully'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false; \$addMemberSuccess = true; @get('{$members_list_endpoint}{$members_list_separator}";
if ($mode === 'groups') {
    $add_member_success_actions .= "group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1') >> select('#{$members_list_target}') | set(html);";
} else {
    $add_member_success_actions .= "org_uuid={$encoded_org_uuid}{$membership_query_fragment}&page=1') >> select('#{$members_list_target}') | set(html);";
}
$add_member_success_actions .= ' setTimeout(() => { $addMemberSuccess = false; $addMemberSubmitting = false; }, 3000);';
$add_member_error_actions = "console.error('Failed to add member'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false;";

$remove_member_success_actions = "console.log('Member removed successfully'); \$removeMemberSubmitting = false; \$membersLoading = false; \$removeMemberModalOpen = false; \$removeMemberSuccess = true; @get('{$members_list_endpoint}{$members_list_separator}";
if ($mode === 'groups') {
    $remove_member_success_actions .= "group_uuid={$encoded_group_uuid}&org_uuid={$encoded_org_uuid}&page=1') >> select('#{$members_list_target}') | set(html);";
} else {
    $remove_member_success_actions .= "org_uuid={$encoded_org_uuid}{$membership_query_fragment}&page=1') >> select('#{$members_list_target}') | set(html);";
}
$remove_member_success_actions .= ' setTimeout(() => { $removeMemberSuccess = false; $removeMemberSubmitting = false; }, 3000);';
$remove_member_error_actions = "console.error('Failed to remove member'); \$removeMemberSubmitting = false; \$membersLoading = false; \$removeMemberModalOpen = false;";
?>

    <?php if ($mode !== 'groups' && $show_edit_permissions) : ?>
        <?php
    $permission_service = new OrgManagement\Services\PermissionService();
        $available_roles = $permission_service->get_available_roles();

        if (!empty($orgman_config['permissions']['prevent_owner_assignment'])) {
            unset($available_roles['membership_owner']);
        }

        $edit_permissions_config = $orgman_config['edit_permissions_modal'] ?? [];
        $edit_allowed_roles = is_array($edit_permissions_config['allowed_roles'] ?? null)
            ? $edit_permissions_config['allowed_roles']
            : [];
        $edit_excluded_roles = is_array($edit_permissions_config['excluded_roles'] ?? null)
            ? $edit_permissions_config['excluded_roles']
            : [];

        $available_roles = OrgHelpers\PermissionHelper::filter_role_choices(
            $available_roles,
            $edit_allowed_roles,
            $edit_excluded_roles
        );

        $form_config = $orgman_config['member_addition_form']['fields'] ?? [];
        $allow_relationship_editing = $orgman_config['member_addition_form']['allow_relationship_type_editing'] ?? false;
        $relationship_types = $orgman_config['relationship_types']['custom_types'] ?? [];
        $update_permissions_endpoint = OrgHelpers\template_url() . 'process/update-permissions';
        $members_list_separator = str_contains($members_list_endpoint, '?') ? '&' : '?';
        $update_permissions_success_actions = "console.log('Permissions updated successfully'); \$editPermissionsSubmitting = false; \$editPermissionsSuccess = true; \$membersLoading = false; \$editPermissionsModalOpen = false; @get('{$members_list_endpoint}{$members_list_separator}org_uuid={$encoded_org_uuid}{$membership_query_fragment}&page=1') >> select('#{$members_list_target}') | set(html); setTimeout(() => \$editPermissionsSuccess = false, 3000);";
        $update_permissions_error_actions = "console.error('Failed to update permissions'); \$editPermissionsSubmitting = false; \$membersLoading = false; \$editPermissionsModalOpen = false;";
        ?>
        <div class="wt_mt-6" data-signals='{"editPermissionsModalOpen": false, "editPermissionsSubmitting": false, "editPermissionsSuccess": false, "currentMemberUuid": "", "currentMemberName": "", "currentMemberRoles": [], "currentMemberRelationshipType": "", "currentMemberDescription": "", "removeMemberModalOpen": false, "removeMemberSubmitting": false, "removeMemberSuccess": false, "currentRemoveMemberUuid": "", "currentRemoveMemberName": "", "currentRemoveMemberEmail": "", "currentRemoveMemberConnectionId": "", "currentRemoveMemberPersonMembershipId": ""}'>
            <dialog id="editPermissionsModal" class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
                data-show="$editPermissionsModalOpen"
                data-effect="if ($editPermissionsModalOpen) el.showModal(); else el.close();"
                data-on:close="($membersLoading = false); $editPermissionsModalOpen = false">
                <div class="wt_bg-white wt_p-6 wt_relative">
                    <button type="button" class="wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
                        data-on:click="$editPermissionsModalOpen = false" data-class_wt_hidden="$editPermissionsSuccess">
                        ×
                    </button>

                    <h2 class="wt_text-2xl wt_font-semibold wt_mb-4">
                        <span data-class_wt_hidden="$currentMemberName === ''">
                            <?php echo esc_html(__('Edit Permissions for', 'wicket-acc')); ?>
                            <span data-text="$currentMemberName"></span>
                        </span>
                        <span data-class_wt_hidden="$currentMemberName !== ''">
                            <?php echo esc_html__('Edit Permissions', 'wicket-acc'); ?>
                        </span>
                    </h2>

                    <div id="update-permissions-messages"></div>

                    <form
                        method="POST"
                        data-on:submit="$editPermissionsSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($update_permissions_endpoint); ?>', { contentType: 'form' })"
                        data-on:submit__prevent-default="true"
                        data-on:success="<?php echo esc_attr($update_permissions_success_actions); ?>"
                        data-on:error="<?php echo esc_attr($update_permissions_error_actions); ?>"
                        data-on:reset="$editPermissionsSubmitting = false; $membersLoading = false">
                        <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                        <input type="hidden" name="membership_uuid" value="<?php echo esc_attr($membership_uuid); ?>">
                        <input type="hidden" name="person_uuid" data-attr:value="$currentMemberUuid">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-update-permissions')); ?>">

                        <?php if ($allow_relationship_editing && !empty($relationship_types)) : ?>
                            <div class="wt_mb-6">
                                <label class="wt_block wt_text-sm wt_font-medium wt_mb-2" for="edit-member-relationship-type">
                                    <?php esc_html_e('Relationship Type', 'wicket-acc'); ?>
                                </label>
                                <select id="edit-member-relationship-type" name="relationship_type"
                                    data-attr:value="$currentMemberRelationshipType"
                                    data-effect="el.value = $currentMemberRelationshipType || ''"
                                    class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                                    <option value=""><?php esc_html_e('Select a relationship type', 'wicket-acc'); ?></option>
                                    <?php foreach ($relationship_types as $type_key => $type_label) : ?>
                                        <option value="<?php echo esc_attr($type_key); ?>">
                                            <?php echo esc_html($type_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($form_config['description']['enabled'] ?? false): ?>
                            <div class="wt_mb-6">
                                <label class="wt_block wt_text-sm wt_font-medium wt_mb-2" for="edit-member-description">
                                    <?php echo esc_html($form_config['description']['label'] ?? __('Description', 'wicket-acc')); ?>
                                </label>
                                <?php if (($form_config['description']['input_type'] ?? 'textarea') === 'text'): ?>
                                    <input type="text" id="edit-member-description" name="description"
                                        data-effect="el.value = $currentMemberDescription || ''"
                                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                                <?php else: ?>
                                    <textarea id="edit-member-description" name="description"
                                        data-effect="el.value = $currentMemberDescription || ''"
                                        class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2"
                                        rows="3"></textarea>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="wt_mb-6">
                            <p class="wt_font-bold wt_mb-3"><?php esc_html_e('Roles', 'wicket-acc'); ?></p>
                            <?php if (!empty($available_roles)) : ?>
                                <div class="wt_space-y-2">
                                    <?php foreach ($available_roles as $slug => $role) : ?>
                                        <div class="wt_flex wt_items-center wt_gap-2">
                                            <label class="wt_flex wt_items-center wt_gap-2 wt_cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="roles[]"
                                                    value="<?php echo esc_attr($slug); ?>"
                                                    class="form-checkbox wt_h-4 wt_w-4 wt_text-bg-interactive wt_rounded wt_focus_ring-bg-interactive"
                                                    data-attr:checked="$currentMemberRoles.includes('<?php echo esc_js($slug); ?>')">
                                                <span class="wt_text-sm wt_text-content"><?php echo esc_html($role); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="wt_text-sm wt_text-content"><?php esc_html_e('No roles available.', 'wicket-acc'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="wt_flex wt_justify-end wt_gap-3" data-class_wt_hidden="$editPermissionsSuccess">
                            <button
                                type="button"
                                data-on:click="$editPermissionsModalOpen = false"
                                class="button button--secondary wt_px-4 wt_py-2 wt_text-sm component-button"
                                data-class:disabled="$editPermissionsSubmitting"
                                data-attr:disabled="$editPermissionsSubmitting"><?php esc_html_e('Cancel', 'wicket-acc'); ?></button>
                            <button
                                type="submit"
                                class="button button--primary wt_inline-flex wt_items-center wt_gap-2 wt_px-4 wt_py-2 wt_text-sm component-button"
                                data-class:disabled="$editPermissionsSubmitting"
                                data-attr:disabled="$editPermissionsSubmitting">
                                <span data-class_wt_hidden="$editPermissionsSubmitting">
                                    <?php esc_html_e('Save Permissions', 'wicket-acc'); ?>
                                </span>
                                <svg
                                    class="wt_h-4 wt_w-4 wt_text-button-label-reversed wt_hidden"
                                    data-class_wt_hidden="!$editPermissionsSubmitting"
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
            </dialog>
        </div>
    <?php endif; ?>

    <?php
    $add_member_endpoint = ($mode === 'groups')
        ? OrgHelpers\template_url() . 'process/add-group-member'
        : OrgHelpers\template_url() . 'process/add-member';
?>

    <dialog id="membersAddModal" class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
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

            <?php if ($mode === 'groups') : ?>
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
                            <?php
                        $groups_config = is_array($orgman_config['groups'] ?? null) ? $orgman_config['groups'] : [];
                $member_role = $groups_config['member_role'] ?? 'member';
                $observer_role = $groups_config['observer_role'] ?? 'observer';
                ?>
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
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            <?php else : ?>
                <div id="add-member-messages-<?php echo esc_attr(sanitize_html_class($org_uuid ?: 'default')); ?>"></div>
                <form name="add_new_person_membership_form" id="add_new_person_membership_form"
                    class="wt_flex wt_flex-col wt_gap-4" method="POST"
                    data-on:submit="if(!$addMemberSubmitting){ $addMemberSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($add_member_endpoint); ?>', { contentType: 'form' }); }"
                    data-on:submit__prevent-default="true"
                    data-on:success="<?php echo esc_attr($add_member_success_actions); ?>"
                    data-on:error="<?php echo esc_attr($add_member_error_actions); ?>"
                    data-on:reset="$addMemberSubmitting = false">
                    <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                    <input type="hidden" name="org_dom_suffix" value="<?php echo esc_attr(sanitize_html_class($org_uuid ?: 'default')); ?>">
                    <input type="hidden" name="membership_id" value="<?php echo esc_attr($membership_uuid); ?>">
                    <input type="hidden" name="included_id" value="<?php echo esc_attr($membersResult['included_id'] ?? ''); ?>">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-add-member')); ?>">

                    <?php
                    $form_config = $orgman_config['member_addition_form']['fields'] ?? [];
                $relationship_types = $orgman_config['relationship_types']['custom_types'] ?? [];
                ?>

                    <?php if ($form_config['first_name']['enabled'] ?? false) : ?>
                        <div>
                            <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-first-name">
                                <?php echo esc_html($form_config['first_name']['label'] ?? __('First Name', 'wicket-acc')); ?>
                                <?php echo ($form_config['first_name']['required'] ?? false) ? '*' : ''; ?>
                            </label>
                            <input type="text" id="new-member-first-name" name="first_name"
                                <?php echo ($form_config['first_name']['required'] ?? false) ? 'required' : ''; ?>
                                class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                        </div>
                    <?php endif; ?>

                    <?php if ($form_config['last_name']['enabled'] ?? false) : ?>
                        <div>
                            <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-last-name">
                                <?php echo esc_html($form_config['last_name']['label'] ?? __('Last Name', 'wicket-acc')); ?>
                                <?php echo ($form_config['last_name']['required'] ?? false) ? '*' : ''; ?>
                            </label>
                            <input type="text" id="new-member-last-name" name="last_name"
                                <?php echo ($form_config['last_name']['required'] ?? false) ? 'required' : ''; ?>
                                class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                        </div>
                    <?php endif; ?>

                    <?php if ($form_config['email']['enabled'] ?? false) : ?>
                        <div>
                            <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-email">
                                <?php echo esc_html($form_config['email']['label'] ?? __('Email Address', 'wicket-acc')); ?>
                                <?php echo ($form_config['email']['required'] ?? false) ? '*' : ''; ?>
                            </label>
                            <input type="email" id="new-member-email" name="email"
                                <?php echo ($form_config['email']['required'] ?? false) ? 'required' : ''; ?>
                                class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2"
                                placeholder="<?php echo esc_attr(__('user@mail.com', 'wicket-acc')); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if (($form_config['relationship_type']['enabled'] ?? false) && !empty($relationship_types)) : ?>
                        <div>
                            <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-relationship-type">
                                <?php echo esc_html($form_config['relationship_type']['label'] ?? __('Relationship Type', 'wicket-acc')); ?>
                                <?php echo ($form_config['relationship_type']['required'] ?? false) ? '*' : ''; ?>
                            </label>
                            <select id="new-member-relationship-type" name="relationship_type"
                                <?php echo ($form_config['relationship_type']['required'] ?? false) ? 'required' : ''; ?>
                                class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                                <option value=""><?php esc_html_e('Select a relationship type', 'wicket-acc'); ?></option>
                                <?php foreach ($relationship_types as $type_key => $type_label) : ?>
                                    <option value="<?php echo esc_attr($type_key); ?>">
                                        <?php echo esc_html($type_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ($form_config['description']['enabled'] ?? false): ?>
                        <div>
                            <label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-description">
                                <?php echo esc_html($form_config['description']['label'] ?? __('Description', 'wicket-acc')); ?>
                                <?php echo ($form_config['description']['required'] ?? false) ? '*' : ''; ?>
                            </label>
                            <?php if (($form_config['description']['input_type'] ?? 'textarea') === 'text'): ?>
                                <input type="text" id="new-member-description" name="description"
                                    <?php echo ($form_config['description']['required'] ?? false) ? 'required' : ''; ?>
                                    class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
                            <?php else: ?>
                                <textarea id="new-member-description" name="description"
                                    <?php echo ($form_config['description']['required'] ?? false) ? 'required' : ''; ?>
                                    class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2"
                                    rows="3"></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                $permissions_field_config = $orgman_config['member_addition_form']['fields']['permissions'] ?? [];
$allowed_roles = $permissions_field_config['allowed_roles'] ?? [];
$excluded_roles = $permissions_field_config['excluded_roles'] ?? [];
$permission_service = new OrgManagement\Services\PermissionService();
$available_roles = $permission_service->get_available_roles();
if (!empty($orgman_config['permissions']['prevent_owner_assignment'])) {
    unset($available_roles['membership_owner']);
}
$available_roles = OrgHelpers\PermissionHelper::filter_role_choices(
    $available_roles,
    is_array($allowed_roles) ? $allowed_roles : [],
    is_array($excluded_roles) ? $excluded_roles : []
);
?>

                    <?php if (!empty($available_roles)) : ?>
                        <fieldset class="wt_flex wt_flex-col wt_gap-2">
                            <legend class="wt_text-sm wt_font-medium"><?php esc_html_e('Security Roles', 'wicket-acc'); ?></legend>
                            <?php foreach ($available_roles as $role_slug => $role_name) : ?>
                                <label class="wt_flex wt_items-center wt_gap-2">
                                    <input type="checkbox" name="roles[]" value="<?php echo esc_attr($role_slug); ?>" class="form-checkbox">
                                    <span><?php echo esc_html($role_name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <div class="wt_flex wt_justify-end wt_gap-3 wt_pt-4" data-class:hidden="$addMemberSuccess">
                        <button type="button" class="button button--secondary component-button"
                            data-on:click="$addMemberModalOpen = false"
                            data-class="{ 'wt_pointer-events-none': $addMemberSubmitting, 'wt_opacity-50': $addMemberSubmitting }"
                            data-attr:aria-disabled="$addMemberSubmitting ? 'true' : 'false'"><?php esc_html_e('Cancel', 'wicket-acc'); ?></button>
                        <button type="submit" class="button button--primary wt_inline-flex wt_items-center wt_gap-2 component-button"
                            data-class="{ 'wt_pointer-events-none': $addMemberSubmitting, 'wt_opacity-50': $addMemberSubmitting }"
                            data-attr:aria-disabled="$addMemberSubmitting ? 'true' : 'false'">
                            <span data-class:hidden="$addMemberSubmitting"><?php esc_html_e('Add Member', 'wicket-acc'); ?></span>
                            <svg class="wt_h-5 wt_w-5 wt_text-button-label-reversed wt_hidden"
                                data-class:hidden="!$addMemberSubmitting" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle class="wt_opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="wt_opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </dialog>

    <?php if ($mode !== 'groups' && $show_add_member_button && $show_bulk_upload) : ?>
        <?php
        $bulk_upload_endpoint = OrgHelpers\template_url() . 'process/bulk-upload-members';
        $bulk_upload_messages_id = 'bulk-upload-messages-' . sanitize_html_class($org_uuid ?: 'default');
        $bulk_upload_wrapper_class = 'wt_rounded-md wt_border wt_border-color wt_bg-white wt_p-4';
        ?>
        <dialog id="membersBulkUploadModal" class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
            data-show="$bulkUploadModalOpen"
            data-effect="if ($bulkUploadModalOpen) el.showModal(); else el.close();"
            data-on:close="($membersLoading = false); $bulkUploadModalOpen = false">
            <div class="wt_bg-white wt_p-6 wt_relative" data-on:click__outside__capture="$bulkUploadModalOpen = false">
                <button type="button" class="wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
                    data-on:click="$bulkUploadModalOpen = false">
                    ×
                </button>
                <?php include __DIR__ . '/members-bulk-upload.php'; ?>
            </div>
        </dialog>
    <?php endif; ?>

    <?php if ($show_remove_button) : ?>
    <dialog id="membersRemoveModal" class="modal wt_m-auto max_wt_md wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
        data-show="$removeMemberModalOpen"
        data-effect="if ($removeMemberModalOpen) el.showModal(); else el.close();"
        data-on:close="($membersLoading = false); $removeMemberModalOpen = false">
        <div class="wt_bg-white wt_p-6 wt_relative">
            <button type="button" class="wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
                data-on:click="$removeMemberModalOpen = false" data-class_wt_hidden="$removeMemberSuccess">×</button>
            <h2 class="wt_text-2xl wt_font-semibold wt_mb-4"><?php esc_html_e('Remove Member', 'wicket-acc'); ?></h2>
            <div id="remove-member-messages"></div>

            <div data-class_wt_hidden="$removeMemberSuccess">
                <p class="wt_mb-6">
                    <span data-class_wt_hidden="$currentRemoveMemberName === ''">
                        <?php echo esc_html__('Are you sure you want to remove this member?', 'wicket-acc'); ?>
                    </span>
                    <span data-class_wt_hidden="$currentRemoveMemberName !== ''">
                        <?php echo esc_html__('Are you sure you want to remove', 'wicket-acc'); ?>
                        <span data-text="$currentRemoveMemberName"></span>
                        <?php echo esc_html__('from this organization?', 'wicket-acc'); ?>
                    </span>
                    <br>
                    <?php esc_html_e('This action cannot be undone.', 'wicket-acc'); ?>
                </p>

                <?php
                $remove_member_endpoint = ($mode === 'groups')
? OrgHelpers\template_url() . 'process/remove-group-member'
: OrgHelpers\template_url() . 'process/remove-member';
?>

                <form method="POST"
                    data-on:submit="$removeMemberSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($remove_member_endpoint); ?>', { contentType: 'form' })"
                    data-on:submit__prevent-default="true"
                    data-on:success="<?php echo esc_attr($remove_member_success_actions); ?>"
                    data-on:error="<?php echo esc_attr($remove_member_error_actions); ?>"
                    data-on:reset="$removeMemberSubmitting = false; $membersLoading = false">

                    <?php if ($mode === 'groups') : ?>
                        <input type="hidden" name="group_uuid" value="<?php echo esc_attr($group_uuid); ?>">
                        <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                        <input type="hidden" name="person_uuid" data-attr:value="$currentRemoveMemberUuid">
                        <input type="hidden" name="group_member_id" data-attr:value="$currentRemoveMemberGroupMemberId">
                        <input type="hidden" name="role" data-attr:value="$currentRemoveMemberRole">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-remove-group-member')); ?>">
                    <?php else : ?>
                        <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
                        <input type="hidden" name="membership_uuid" value="<?php echo esc_attr($membership_uuid); ?>">
                        <input type="hidden" name="person_uuid" data-attr:value="$currentRemoveMemberUuid">
                        <input type="hidden" name="person_name" data-attr:value="$currentRemoveMemberName">
                        <input type="hidden" name="person_email" data-attr:value="$currentRemoveMemberEmail">
                        <input type="hidden" name="connection_id" data-attr:value="$currentRemoveMemberConnectionId">
                        <input type="hidden" name="person_membership_id" data-attr:value="$currentRemoveMemberPersonMembershipId">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-remove-member')); ?>">
                    <?php endif; ?>

                    <div class="wt_flex wt_justify-end wt_gap-3">
                        <button type="button" data-on:click="$removeMemberModalOpen = false"
                            class="button button--secondary wt_px-4 wt_py-2 wt_text-sm component-button"
                            data-class:disabled="$removeMemberSubmitting"
                            data-attr:disabled="$removeMemberSubmitting"><?php esc_html_e('Cancel', 'wicket-acc'); ?></button>
                        <button type="submit" class="button button--danger wt_inline-flex wt_items-center wt_gap-2 wt_px-4 wt_py-2 wt_text-sm component-button"
                            data-class:disabled="$removeMemberSubmitting"
                            data-attr:disabled="$removeMemberSubmitting">
                            <span data-class_wt_hidden="$removeMemberSubmitting"><?php esc_html_e('Remove Member', 'wicket-acc'); ?></span>
                            <svg class="wt_h-4 wt_w-4 wt_text-white wt_hidden"
                                data-class_wt_hidden="!$removeMemberSubmitting" viewBox="0 0 24 24" fill="none"
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
    <?php endif; ?>
</div>
