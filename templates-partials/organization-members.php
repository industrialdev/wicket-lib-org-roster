<?php

declare(strict_types=1);

namespace OrgManagement\Templates;

// Ensure this file is not accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Renders the organization members partial.
 *
 * This template displays the list of organization members with search and filter functionality.
 * It uses Datastar for dynamic updates.
 *
 * @package CCHL
 * @since 1.0.0
 */

if (isset($args['org_uuid'])) {
    $org_uuid = sanitize_text_field((string) $args['org_uuid']);
} elseif (isset($_GET['org_uuid'])) {
    $org_uuid = sanitize_text_field((string) $_GET['org_uuid']);
}
$org_uuid_dom_suffix = sanitize_html_class($org_uuid ?? 'default');
$lang = wicket_get_current_language();

\OrgManagement\Helpers\Helper::log_debug('[OrgMan] Rendering organization-members partial', [
    'org_uuid' => $org_uuid,
]);

// Fetch organization members if the Wicket function exists.
$membership_service = new \OrgManagement\Services\MembershipService();
$config_service = new \OrgManagement\Services\ConfigService();
$member_service = new \OrgManagement\Services\MemberService($config_service);
$permission_service = new \OrgManagement\Services\PermissionService();

$additional_seats_service = new \OrgManagement\Services\AdditionalSeatsService($config_service);

// Load org management configuration
$orgman_config = \OrgManagement\Config\OrgManConfig::get();
$requested_membership_uuid = isset($_GET['membership_uuid']) ? sanitize_text_field((string) wp_unslash($_GET['membership_uuid'])) : '';

$membershipUuid = $requested_membership_uuid !== ''
    ? $requested_membership_uuid
    : $membership_service->getMembershipForOrganization($org_uuid);

$membersResult = [
    'members'    => [],
    'pagination' => [
        'currentPage' => 1,
        'totalPages'  => 1,
        'pageSize'    => 10,
        'totalItems'  => 0,
    ],
    'org_uuid'   => $org_uuid,
    'query'      => '',
];

if (!empty($membershipUuid)) {
    try {
        $membersResult = $member_service->get_members(
            $membershipUuid,
            $org_uuid,
            [
                'page' => 1,
                'size' => 10,
            ]
        );
    } catch (\Throwable $e) {
        \OrgManagement\Helpers\Helper::log_error('[OrgMan] Failed to load members list: ' . $e->getMessage(), [
            'membership_uuid' => $membershipUuid,
        ]);
    }
}

if (!isset($membersResult['pagination'])) {
    $membersResult['pagination'] = [
        'currentPage' => 1,
        'totalPages'  => 1,
        'pageSize'    => 10,
        'totalItems'  => count($membersResult['members'] ?? []),
    ];
}

$members = $membersResult['members'] ?? [];
$pagination = $membersResult['pagination'];
$query = $membersResult['query'] ?? '';
$totalMemberCount = (int) ($pagination['totalItems'] ?? count($members));
$member_list_config = is_array($orgman_config['ui']['member_list'] ?? null)
    ? $orgman_config['ui']['member_list']
    : [];
$show_bulk_upload = (bool) ($member_list_config['show_bulk_upload'] ?? false);

\OrgManagement\Helpers\Helper::log_debug('[OrgMan] Members render summary', [
    'render_count'      => count($members),
    'total_member_count' => $totalMemberCount,
    'current_page'      => $pagination['currentPage'] ?? 1,
    'page_size'         => $pagination['pageSize'] ?? 10,
]);

$available_roles = $permission_service->get_available_roles();

$containerId = 'members-list-container-' . $org_uuid_dom_suffix;
$membersListEndpoint = \OrgManagement\Helpers\template_url() . 'members-list';
$membersListSeparator = str_contains($membersListEndpoint, '?') ? '&' : '?';
$encodedOrgUuid = rawurlencode((string) $org_uuid);
$searchAction = '';
$searchSuccess = '';

if (!empty($membershipUuid)) {
    $membership_query_fragment = '&membership_uuid=' . rawurlencode((string) $membershipUuid);
    $searchAction = "@get('{$membersListEndpoint}{$membersListSeparator}org_uuid={$encodedOrgUuid}{$membership_query_fragment}&page=1&query=' + encodeURIComponent(\$searchQuery))";
    $searchSuccess = wp_sprintf("select('#%s') | set(html)", $containerId);
}

$signals = [
    'searchQuery' => $query,
];
$use_unified_view = (bool) ($orgman_config['ui']['member_view']['use_unified'] ?? false);
if ($use_unified_view) {
    $mode = (string) ($config_service->get_roster_mode() ?? 'direct');
    $members = $membersResult['members'] ?? [];
    $pagination = $membersResult['pagination'] ?? [];
    $query = $membersResult['query'] ?? '';
    $membership_uuid = $membershipUuid ?? '';
    $members_list_endpoint = $membersListEndpoint;
    $members_list_target = $containerId;
    include __DIR__ . '/members-view-unified.php';

    return;
}
?>
	<div class="members-list wt_relative"
	data-signals:='{"membersLoading": false, "bulkUploadModalOpen": false, "bulkUploadSubmitting": false, "addMemberModalOpen": false, "addMemberSubmitting": false, "addMemberSuccess": false}'
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
	<?php /* Seat count moved to members-list.php for dynamic refresh */ ?>
	<div id="org-members-search-form-<?php echo esc_attr($org_uuid); ?>"
		data-signals:='<?php echo wp_json_encode($signals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'
		class="members-search wt_flex wt_items-center wt_gap-2 wt_mb-6">
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
if (!empty($searchAction)) {
    $searchLengthCondition = '((($searchQuery || \'\').length >= 3) || (($searchQuery || \'\').length === 0))';
    $searchInputAction = $searchLengthCondition . ' && ' . $searchAction;
}
?>
			<input type="text"
				id="search-input-<?php echo esc_attr($org_uuid); ?>"
				data-bind="searchQuery"
				class="members-search__input wt_border wt_border-color wt_text-content wt_text-sm wt_rounded-md wt_focus_ring-2 wt_focus_ring-bg-interactive wt_focus_border-bg-interactive wt_block wt_w-full wt_pl-10 wt_p-2.5"
				placeholder="<?php esc_attr_e('Start typing to search for members...', 'wicket-acc'); ?>"
				<?php if (!empty($searchInputAction)) : ?>
			data-on:input__debounce.700ms="<?php echo esc_attr($searchInputAction); ?>"
			data-on:success="<?php echo esc_attr($searchSuccess); ?>"
			data-indicator:members-loading
			data-attr:disabled="$membersLoading"
			<?php endif; ?>
			>
		</div>
		<?php
        $searchButtonAction = !empty($searchAction) ? $searchAction : '';
$clearButtonAction = '';
if (!empty($searchAction)) {
    $clearButtonAction = sprintf('(($searchQuery = \'\'), %s)', $searchAction);
}
?>
		<div class="members-search__actions wt_flex wt_items-center wt_gap-2">
			<button
				<?php if (!empty($searchButtonAction)) : ?>data-on:click="<?php echo esc_attr($searchButtonAction); ?>"<?php endif; ?>
				<?php if (!empty($searchSuccess)) : ?>data-on:success="<?php echo esc_attr($searchSuccess); ?>"<?php endif; ?>
				data-show="!$searchQuery || $searchQuery.trim() === ''"
				data-indicator:members-loading
				data-attr:disabled="$membersLoading"
				class="members-search__submit button button--primary wt_whitespace-nowrap component-button"
				<?php disabled(empty($membershipUuid)); ?>><?php esc_html_e('Search', 'wicket-acc'); ?></button>
			<button
				<?php if (!empty($clearButtonAction)) : ?>data-on:click="<?php echo esc_attr($clearButtonAction); ?>"<?php endif; ?>
				<?php if (!empty($searchSuccess)) : ?>data-on:success="<?php echo esc_attr($searchSuccess); ?>"<?php endif; ?>
				data-show="$searchQuery && $searchQuery.trim() !== ''"
				data-indicator:members-loading
				data-attr:disabled="$membersLoading"
				class="members-search__clear button button--secondary wt_whitespace-nowrap component-button"
				<?php disabled(empty($membershipUuid)); ?>><?php esc_html_e('Clear', 'wicket-acc'); ?></button>
		</div>
	</div>
    </div>
	<?php if (empty($membershipUuid)) : ?>
	</p>
	<?php else : ?>
	<?php
$members_list_endpoint = $membersListEndpoint;
	    $members_list_target = $containerId;
	    $org_uuid_for_partial = $org_uuid;
	    $use_unified_member_list = (bool) ($orgman_config['ui']['member_list']['use_unified'] ?? false);
	    if ($use_unified_member_list) {
	        $mode = (string) ($config_service->get_roster_mode() ?? 'direct');
	        $members = $membersResult['members'] ?? [];
	        $pagination = $membersResult['pagination'] ?? [];
	        $query = $membersResult['query'] ?? '';
	        $membership_uuid = $membershipUuid ?? '';
	        include __DIR__ . '/members-list-unified.php';
	    } else {
	        include __DIR__ . '/members-list.php';
	    }

	    $membership_query_fragment = $membershipUuid ? '&membership_uuid=' . rawurlencode((string) $membershipUuid) : '';
	    $add_member_success_actions = "console.log('Member added successfully'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false; \$addMemberSuccess = true; @get('{$membersListEndpoint}{$membersListSeparator}org_uuid={$encodedOrgUuid}{$membership_query_fragment}&page=1') >> select('#{$containerId}') | set(html); setTimeout(() => { \$addMemberSuccess = false; \$addMemberSubmitting = false; }, 3000);";
	    $add_member_error_actions = "console.error('Failed to add member'); \$addMemberSubmitting = false; \$membersLoading = false; \$addMemberModalOpen = false;";
	    $add_member_endpoint = \OrgManagement\Helpers\template_url() . 'process/add-member';
	    ?>

	<div class="wt_mt-6">
		<?php /* Add Member button moved to members-list.php for dynamic refresh */ ?>

		<?php
	        // Check if user can purchase additional seats
	        $can_purchase_seats = $additional_seats_service->can_purchase_additional_seats($org_uuid);
	    $purchase_url = $additional_seats_service->get_purchase_form_url($org_uuid, $membershipUuid);

	    // Debug logging for administrators
	    if (current_user_can('administrator')) {
	        \OrgManagement\Helpers\Helper::log_debug('[OrgMan Debug] CTA visibility check', [
	            'org_uuid' => $org_uuid,
	            'membershipUuid' => $membershipUuid,
	            'can_purchase_seats' => $can_purchase_seats,
	            'purchase_url' => $purchase_url,
	            'additional_seats_enabled' => $config_service->is_additional_seats_enabled(),
	            'current_user_roles' => wp_get_current_user()->roles,
	            'current_user_login' => wp_get_current_user()->user_login,
	        ]);
	    }

	    if ($can_purchase_seats && !empty($purchase_url)):
	        ?>
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

		<?php if (\OrgManagement\Helpers\PermissionHelper::can_add_members($org_uuid)): ?>
		<dialog id="membersAddModal"
			class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
			data-show="$addMemberModalOpen" data-effect="if ($addMemberModalOpen) el.showModal(); else el.close();"
			data-on:close="($membersLoading = false); $addMemberModalOpen = false">
			<div class="wt_bg-white wt_p-6 wt_relative" data-on:click__outside__capture="$addMemberModalOpen = false">
				<button type="button" class="orgman-modal__close wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
					data-on:click="$addMemberModalOpen = false" data-class:hidden="$addMemberSuccess">
					×
				</button>

				<h2 class="wt_text-2xl wt_font-semibold wt_mb-4">
					<?php esc_html_e('Add Member', 'wicket-acc'); ?>
				</h2>

				<div
					id="add-member-messages-<?php echo esc_attr($org_uuid_dom_suffix); ?>">
				</div>

				<form name="add_new_person_membership_form" id="add_new_person_membership_form"
					class="wt_flex wt_flex-col wt_gap-4" method="POST"
					data-on:submit="if(!$addMemberSubmitting){ $addMemberSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($add_member_endpoint); ?>', { contentType: 'form' }); }"
					data-on:submit__prevent-default="true"
					data-on:success="<?php echo esc_attr($add_member_success_actions); ?>"
					data-on:error="<?php echo esc_attr($add_member_error_actions); ?>"
					data-on:reset="$addMemberSubmitting = false">
					<input type="hidden" name="org_uuid"
						value="<?php echo esc_attr($org_uuid); ?>">
					<input type="hidden" name="org_dom_suffix"
						value="<?php echo esc_attr($org_uuid_dom_suffix); ?>">
					<input type="hidden" name="membership_id"
						value="<?php echo esc_attr($membershipUuid); ?>">
					<input type="hidden" name="included_id"
						value="<?php echo esc_attr($membersResult['included_id'] ?? ''); ?>">
					<input type="hidden" name="nonce"
						value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-add-member')); ?>">

					<?php
	                // Render configurable form fields
	                $form_config = $orgman_config['member_addition_form']['fields'];
		    $relationship_types = $orgman_config['relationship_types']['custom_types'] ?? [];
		    ?>

					<?php if ($form_config['first_name']['enabled'] ?? false): ?>
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

					<?php if ($form_config['last_name']['enabled'] ?? false): ?>
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

					<?php if ($form_config['email']['enabled'] ?? false): ?>
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

					<?php if ($form_config['relationship_type']['enabled'] ?? false && !empty($relationship_types)): ?>
					<div>
						<label class="wt_block wt_text-sm wt_font-medium wt_mb-1" for="new-member-relationship-type">
							<?php echo esc_html($form_config['relationship_type']['label'] ?? __('Relationship Type', 'wicket-acc')); ?>
							<?php echo ($form_config['relationship_type']['required'] ?? false) ? '*' : ''; ?>
						</label>
						<select id="new-member-relationship-type" name="relationship_type"
							<?php echo ($form_config['relationship_type']['required'] ?? false) ? 'required' : ''; ?>
							class="wt_w-full wt_border wt_border-color wt_rounded-md wt_p-2">
							<option value="">
								<?php esc_html_e('Select a relationship type', 'wicket-acc'); ?>
							</option>
							<?php foreach ($relationship_types as $type_key => $type_label): ?>
							<option
								value="<?php echo esc_attr($type_key); ?>">
								<?php echo esc_html($type_label); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php
		    $permissions_field_config = $orgman_config['member_addition_form']['fields']['permissions'] ?? [];
		    $allowed_roles = $permissions_field_config['allowed_roles'] ?? [];
		    $excluded_roles = $permissions_field_config['excluded_roles'] ?? [];
		    // Filter out membership_owner if configured to prevent assignment
		    if (!empty($orgman_config['permissions']['prevent_owner_assignment'])) {
		        unset($available_roles['membership_owner']);
		    }
		    $available_roles = \OrgManagement\Helpers\PermissionHelper::filter_role_choices(
		        $available_roles,
		        is_array($allowed_roles) ? $allowed_roles : [],
		        is_array($excluded_roles) ? $excluded_roles : []
		    );
		    ?>

					<?php if (!empty($available_roles)) : ?>
					<fieldset class="wt_flex wt_flex-col wt_gap-2">
						<legend class="wt_text-sm wt_font-medium">
							<?php esc_html_e('Security Roles', 'wicket-acc'); ?>
						</legend>
						<?php foreach ($available_roles as $role_slug => $role_name) : ?>
						<label class="wt_flex wt_items-center wt_gap-2">
							<input type="checkbox" name="roles[]"
								value="<?php echo esc_attr($role_slug); ?>"
								class="form-checkbox">
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
							<span data-class:hidden="$addMemberSubmitting">
								<?php esc_html_e('Add Member', 'wicket-acc'); ?>
							</span>
							<svg class="wt_h-5 wt_w-5 wt_text-button-label-reversed wt_hidden"
								data-class:hidden="!$addMemberSubmitting" viewBox="0 0 24 24" fill="none"
								xmlns="http://www.w3.org/2000/svg">
								<circle class="wt_opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
									stroke-width="4"></circle>
								<path class="wt_opacity-75" fill="currentColor"
									d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
								</path>
							</svg>
						</button>
					</div>
				</form>
			</div>
		</dialog>

		<?php if ($show_bulk_upload) : ?>
		<?php
		    $bulk_upload_endpoint = \OrgManagement\Helpers\template_url() . 'process/bulk-upload-members';
		    $bulk_upload_messages_id = 'bulk-upload-messages-' . sanitize_html_class($org_uuid ?: 'default');
		    $membership_uuid = $membershipUuid;
		    $bulk_upload_wrapper_class = 'wt_rounded-md wt_border wt_border-color wt_bg-white wt_p-4';
		    ?>
		<dialog id="membersBulkUploadModal"
			class="modal wt_m-auto max_wt_3xl wt_rounded-md wt_shadow-md backdrop_wt_bg-black-50"
			data-show="$bulkUploadModalOpen"
			data-effect="if ($bulkUploadModalOpen) el.showModal(); else el.close();"
			data-on:close="($membersLoading = false); $bulkUploadModalOpen = false">
			<div class="wt_bg-white wt_p-6 wt_relative" data-on:click__outside__capture="$bulkUploadModalOpen = false">
				<button type="button" class="orgman-modal__close wt_absolute wt_right-4 wt_top-4 wt_text-lg wt_font-semibold"
					data-on:click="$bulkUploadModalOpen = false">
					×
				</button>
				<?php include __DIR__ . '/members-bulk-upload.php'; ?>
			</div>
		</dialog>
		<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>
