<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$org_uuid = isset($org_uuid) ? (string) $org_uuid : '';
$group_uuid = isset($group_uuid) ? (string) $group_uuid : '';
$membership_uuid = isset($membership_uuid) ? (string) $membership_uuid : '';
$bulk_upload_endpoint = isset($bulk_upload_endpoint)
    ? (string) $bulk_upload_endpoint
    : \OrgManagement\Helpers\template_url() . 'process/bulk-upload-members';
$bulk_upload_dom_suffix_raw = $org_uuid !== '' ? $org_uuid : ($group_uuid !== '' ? $group_uuid : 'default');
$bulk_upload_dom_suffix = sanitize_html_class($bulk_upload_dom_suffix_raw);
$bulk_upload_messages_id = isset($bulk_upload_messages_id)
    ? (string) $bulk_upload_messages_id
    : 'bulk-upload-messages-' . $bulk_upload_dom_suffix;
$bulk_upload_wrapper_class = isset($bulk_upload_wrapper_class)
    ? (string) $bulk_upload_wrapper_class
    : 'wt_mt-6 wt_rounded-md wt_border wt_border-color wt_bg-white wt_p-4';

$library_base_path = dirname(__DIR__);
$content_dir = defined('WP_CONTENT_DIR') ? (string) WP_CONTENT_DIR : '';
$library_base_url = trailingslashit((string) content_url(''));
if ($content_dir !== '' && strpos($library_base_path, $content_dir) === 0) {
    $relative_path = ltrim(str_replace($content_dir, '', $library_base_path), '/');
    $library_base_url = trailingslashit((string) content_url($relative_path));
}
$library_base_url = trailingslashit((string) apply_filters('wicket/acc/orgman/base_url', $library_base_url));
$csv_template_url = $library_base_url . 'public/templates/roster_template.csv';
?>

<div class="<?php echo esc_attr($bulk_upload_wrapper_class); ?>">
    <h3 class="wt_text-base wt_font-semibold wt_mb-2"><?php esc_html_e('Bulk Upload Members', 'wicket-acc'); ?></h3>
    <p class="wt_text-sm wt_text-content wt_mb-3">
        <?php esc_html_e('Upload a CSV file to add multiple members at once. Existing active members are skipped automatically.', 'wicket-acc'); ?>
    </p>

    <div id="<?php echo esc_attr($bulk_upload_messages_id); ?>" class="wt_mb-3"></div>

    <div class="wt_mb-3">
        <a
            class="button button--secondary component-button"
            href="<?php echo esc_url($csv_template_url); ?>"
            download="roster_template.csv">
            <?php esc_html_e('Download CSV Template', 'wicket-acc'); ?>
        </a>
    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        data-on:submit="$bulkUploadSubmitting = true; $membersLoading = true; @post('<?php echo esc_js($bulk_upload_endpoint); ?>', { contentType: 'form' })"
        data-on:submit__prevent-default="true"
        data-on:error="$bulkUploadSubmitting = false; $membersLoading = false">
        <input type="hidden" name="org_uuid" value="<?php echo esc_attr($org_uuid); ?>">
        <input type="hidden" name="membership_uuid" value="<?php echo esc_attr($membership_uuid); ?>">
        <input type="hidden" name="group_uuid" value="<?php echo esc_attr($group_uuid); ?>">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wicket-orgman-bulk-upload-members')); ?>">

        <label class="wt_block wt_text-sm wt_font-medium wt_mb-2" for="bulk-upload-file-<?php echo esc_attr($bulk_upload_dom_suffix); ?>">
            <?php esc_html_e('CSV File', 'wicket-acc'); ?>
        </label>
        <input
            id="bulk-upload-file-<?php echo esc_attr($bulk_upload_dom_suffix); ?>"
            type="file"
            name="bulk_file"
            accept=".csv,text/csv"
            required
            class="wt_block wt_w-full wt_text-sm wt_mb-3">

        <p class="wt_text-xs wt_text-content wt_mb-3">
            <?php esc_html_e('Expected columns: First Name, Last Name, Email Address, Roles', 'wicket-acc'); ?>
        </p>

        <button
            type="submit"
            class="button button--primary component-button"
            data-attr:disabled="$bulkUploadSubmitting || $membersLoading"
            data-text="$bulkUploadSubmitting ? '<?php echo esc_js(__('Uploading...', 'wicket-acc')); ?>' : '<?php echo esc_js(__('Upload and Add Members', 'wicket-acc')); ?>'">
            <?php esc_html_e('Upload and Add Members', 'wicket-acc'); ?>
        </button>
    </form>
</div>
