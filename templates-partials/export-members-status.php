<?php

/**
 * Export job status partial.
 *
 * Variables:
 *   $job_id (string)
 *   $status (array|null) from MemberExportService::getJobStatus()
 */
if (!defined('ABSPATH')) {
    exit;
}

$job_id = isset($job_id) ? sanitize_key((string) $job_id) : '';
$status = isset($status) && is_array($status) ? $status : null;

if ($status === null || $job_id === '') {
    return;
}

$state = (string) ($status['status'] ?? '');
$total = (int) ($status['total_processed'] ?? 0);
$pages = $status['total_pages'];
$current = (int) ($status['current_page'] ?? 1);
?>

<div class="wt_export-status">
    <?php if ($state === 'completed') : ?>
        <div class="wt_bg-green-100 wt_border wt_border-green-400 wt_text-green-700 wt_px-4 wt_py-3 wt_rounded-sm">
            <?php echo esc_html(sprintf(
                /* translators: %d: number of members exported */
                _n('Export complete — %d member exported.', 'Export complete — %d members exported.', $total, 'wicket-acc'),
                $total
            )); ?>
        </div>
    <?php elseif ($state === 'failed') : ?>
        <div class="wt_bg-red-100 wt_border wt_border-red-400 wt_text-red-700 wt_px-4 wt_py-3 wt_rounded-sm">
            <?php esc_html_e('Export failed. Please try again or contact support.', 'wicket-acc'); ?>
        </div>
    <?php elseif (in_array($state, ['queued', 'processing'], true)) : ?>
        <div class="wt_bg-blue-100 wt_border wt_border-blue-400 wt_text-blue-700 wt_px-4 wt_py-3 wt_rounded-sm">
            <?php if ($pages !== null) : ?>
                <?php echo esc_html(sprintf(
                    /* translators: 1: current page, 2: total pages */
                    __('Export in progress — page %1$d of %2$d…', 'wicket-acc'),
                    $current,
                    (int) $pages
                )); ?>
            <?php else : ?>
                <?php esc_html_e('Export queued — processing will begin shortly…', 'wicket-acc'); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
